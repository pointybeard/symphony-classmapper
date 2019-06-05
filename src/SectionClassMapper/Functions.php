<?php

declare(strict_types=1);

namespace Symphony\SectionClassMapper\SectionClassMapper;

function create(string $alias, string $sectionHandle): string
{
    if (class_exists($alias)) {
        throw new \Exception(sprintf('Unable to create model. Returned: Class %s already exists in global scope', $alias));
    }

    $randomString = function ($length = 32) {
        return substr(hash('sha512', time().microtime()), 0, $length);
    };

    $randomClassName = function (string $prefix = null, string $namespace = null) use ($randomString) {
        do {
            $classname = $prefix.$randomString();
        } while (class_exists("{$namespace}\\{$classname}"));

        return $classname;
    };

    $namespace = '\\Symphony\\SectionClassMapper\\SectionClassMapper';
    $wrapperShortName = $randomClassName('modelCreationWrapper', $namespace);
    $wrapper = "{$namespace}\\{$wrapperShortName}";

    /*
     * Unfortunately, resorting to using eval() is necessary here. Anonymous
     * classes will return the same instance if the wrapping class/function
     * is the same. i.e. this function will ALWAYS return the exact same
     * instance of the anonymous class. This is a huge problem since
     * self::class, used in symphonySectionHandle(), will always be the same
     * no matter how many runtime model classes are created. The
     * ModelRegistry::lookup() will only ever return the section handle for
     * the most recently created class.
     *
     * To solve this, the wrapper class name is dynamically generated to ensure
     * a new instance of the anonymous class is always returned.
     *
     * The use of eval() does not get any user generated content
     * so it is not possible for malicious user code to be executed.
     */
    eval(sprintf(
        '
        namespace %s;
        final class %s {
            public static function generate() {
                return get_class(new class() extends AbstractModel implements Interfaces\FilterableModelInterface {
                    use Traits\hasModelTrait;
                    use Traits\HasFilterableModelTrait;
                    public const SECTION = \'%s\';
                });
            }
        }',
        ltrim($namespace, '\\'),
        $wrapperShortName,
        $sectionHandle
    ));

    $classname = $wrapper::generate();
    class_alias($classname, $alias);

    return $classname;
}
