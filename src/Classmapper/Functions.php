<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Classmapper;

use pointybeard\Helpers\Functions\Strings;
use pointybeard\Helpers\Foundation\Factory;

if (!function_exists(__NAMESPACE__.'\create')) {
    function create(string $alias, string $sectionHandle): string
    {
        if (class_exists($alias)) {
            throw new Exceptions\ClassmapperException(sprintf('Unable to create model. Class %s already exists in global scope', $alias));
        }

        $namespace = __NAMESPACE__;
        $wrapperShortName = Strings\random_unique_classname('modelCreationWrapper', $namespace);
        $wrapper = "{$namespace}\\{$wrapperShortName}";

        /*
         * Unfortunately, resorting to using eval() is necessary here. Anonymous
         * classes will return the same instance if the wrapping class/function
         * is the same. i.e. this function will ALWAYS return the exact same
         * instance of the anonymous class. This is a huge problem since each
         * model class needs to know which section it relates to.
         *
         * To solve this, the wrapper class name is dynamically generated to
         * ensure a new instance of the anonymous class is always returned.
         *
         * The use of eval() does not get any user generated content
         * so it is not possible for malicious user code to be executed. The use
         * of Factory\ClassRegistry keeps the provided $sectionHandle value
         * silo'd away so they cannot modify the resultant class
         */
        eval(sprintf(
            'namespace %s;
            use pointybeard\Helpers\Foundation\Factory;
            final class %s {
                public static function generate() {
                    return get_class(new class() extends AbstractModel implements Interfaces\FilterableModelInterface {
                        use Traits\HasModelTrait;
                        use Traits\HasFilterableModelTrait;
                        public function getSectionHandle(): ?string
                        {
                            [$sectionHandle] = Factory\ClassRegistry::lookup(self::class);
                            return $sectionHandle;
                        }
                    });
                }
            }',
            ltrim($namespace, '\\'),
            $wrapperShortName,
            $sectionHandle
        ));

        $classname = $wrapper::generate();
        class_alias($classname, $alias);
        Factory\ClassRegistry::register(
            $classname,
            $sectionHandle
        );

        return $alias;
    }
}
