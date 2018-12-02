<?php

namespace Symphony\ClassMapper\Lib;

use \Symphony, \EntryManager, \SectionManager, \XMLElement;
use SymphonyPDO;
use Symphony\ClassMapper\Lib\Exceptions;

/**
 * AbstractClassMapper
 * Does all the heavy lifing for the Class Mapper. Extend this ClassMapper
 * to use this library.
 */
abstract class AbstractClassMapper
{
    const FLAG_ARRAY = 0x0001;
    const FLAG_BOOL = 0x0002;
    const FLAG_FILE = 0x0004;
    const FLAG_INT = 0x0008;
    const FLAG_STR = 0x0010;
    const FLAG_FLOAT = 0x0020;
    const FLAG_CURRENCY = 0x0040;
    const FLAG_NULL = 0x0080;

    /**
     * Holds the actual data for this model
     * @var array
     */
    protected $properties=[];

    /**
     * This is toggled to true when any properties are changed
     * @type boolean
     */
    protected $hasBeenModified = false;

    /**
     * Derives a properies name by turning the hythenated handle producted
     * by symphony into camelCase. e.g. some-field-handle => some-field-handle
     * @param string $handle Handle of the field to convert
     * @return string        camelCase member name
     */
    protected static function handleToClassMemberName($handle) {
        $bits = explode('-', $handle);
        $result = array_shift($bits);
        if(count($bits) > 0) {
            $result .= implode("", array_map('ucfirst', $bits));
        }
        return $result;
    }

    /**
     * Generates an XMLElement document representation of the data stored
     * in the model.
     * @return XMLElement The XML representation of this model
     */
    public function toXml() {
        $classname = array_pop(explode('\\', get_called_class()));
        $xml = new XMLElement($classname, NULL, ['id' => $this->id]);
        foreach(static::getData() as $key => $values) {
            if(!is_array($values)) {
                $values = [$values];
            }

            foreach($values as $v) {
                $xml->appendChild(new XMLElement($key, \General::sanitize($v)));
            }
        }

        return $xml;
    }

    /**
     * Takes input and produces an array of possible pluralised versions
     * of that input. Note that this method is just a convienence and does not
     * verify that the output are actually words.
     * @param  string $input The input to pluralised
     * @return array         An array containing possible pluraised versions of
     *                       the input.
     * @usedby getSectionHandleFromClassName()
     */
    private static function pluralise($input) {
        return ["{$input}s", "{$input}es", substr($input, 0, -1) . "ies"];
    }

    /**
     * Determines the section handle
     * @return string            Returns the handle of the section
     * @throws SectionNotFoundException
     * @usedby getSectionId(), save()
     */
    private static function getSectionHandleFromClassName() {

        if(is_null(static::$section) || empty(static::$section)) {

            if(defined(get_called_class() . "::SECTION")){

                // The next part expects to get an array of possible section
                // handles. Given the child class has a pre-defined
                // section mapping, use that.
                $sectionHandles = [static::SECTION];

            } else {

                // Let figure out the section handle
                $class = strtolower(array_pop(explode("\\", get_called_class())));

                // Assume it is singular, and look for a pluralised section handles
                $sectionHandles = self::pluralise($class);
            }

            // Check the database for a matching section
            $db = SymphonyPDO\Loader::instance();
            $query = $db->prepare(
                sprintf('SELECT SQL_CALC_FOUND_ROWS `id`, `handle` FROM `tbl_sections` WHERE `handle` IN ("%s")', implode('", "', $sectionHandles))
            );

            $query->execute();

            // Unable to find any matching section
            if ($query->rowCount() <= 0) {
                throw new Exceptions\SectionNotFoundException(
                    "Unable to find section from class name '".get_called_class()."': no section could be located"
                );

            // Result was ambiguous. Pluraisation returned more than 1 matching section
            } elseif($query->rowCount() > 1) {
                throw new Exceptions\SectionNotFoundException(
                    "Unable to find section from class name '".get_called_class()."': ambiguous section name. Pluralisation returned more than 1 result."
                );
            }

            static::$section = $query->fetch(\PDO::FETCH_OBJ)->handle;
        }

        return static::$section;
    }

    /**
     * Takes the data, and generates a key/value pair array using the field
     * handle as the key.
     *
     * @return array key/value pairs
     */
    protected function getData()
    {
        $data = [];

        self::findSectionFields();

        foreach(static::$sectionFields as $fieldHandle => $fieldId) {
            $classMemberName = static::$fieldMapping[$fieldHandle]['classMemberName'];
            $flags = static::$fieldMapping[$fieldHandle]['flags'];
            $data[$fieldHandle] = $this->$classMemberName;

            // ClassMapper currently doesn't support uploading files. Just send
            // along the file name so we don't trigger the Upload field to think
            // this is an upload attempt.
            if(self::isFlagSet($flags, self::FLAG_FILE)) {
                $data[$fieldHandle] = $data[$fieldHandle]['file'];
            }

            // The BOOL flag, which is ostensibliy a checkbox field, needs to
            // be converted into either 'Yes' or 'No'.
            if(self::isFlagSet($flags, self::FLAG_BOOL)) {
                $func = function($input) {
                    return ($input === true || strtolower($input) == 'yes')
                        ? "Yes"
                        : "No"
                    ;
                };

                if(self::isFlagSet($flags, self::FLAG_ARRAY)) {
                    $data[$fieldHandle] = array_map($func, $this->$classMemberName);

                } else {
                    $data[$fieldHandle] = $func($this->$classMemberName);
                }
            }
        }

        return $data;
    }

    /**
     * Convienence method for determining if a FLAG_* constant is set
     *
     * @return boolean true if the flag is set
     */
    protected static function isFlagSet($flags, $flag) {
        // Flags support bitwise operators so it's easy to see
        // if one has been set.
        return ($flags & $flag) == $flag;
    }

    /**
     * Builds out the SQL needed to fetch data for this object
     * @param  string $where custom SQL WHERE clause to append
     * @return string         the SQL to be executed
     */
    protected static function fetchSQL($where=1)
    {
        self::findSectionFields();

        $sql = "SELECT SQL_CALC_FOUND_ROWS e.id as `id`, %s
        FROM `tbl_entries` AS `e` %s
        WHERE e.section_id = %d AND %s
        ORDER BY e.id ASC";

        $sqlFields = $sqlJoins = [];

        foreach(static::$sectionFields as $fieldHandle => $fieldId) {

            $databaseFieldName = static::$fieldMapping[$fieldHandle]['databaseFieldName'];
            $classMemberName = static::$fieldMapping[$fieldHandle]['classMemberName'];
            $joinTableName = static::$fieldMapping[$fieldHandle]['joinTableName'];
            $flags = static::$fieldMapping[$fieldHandle]['flags'];

            if (self::isFlagSet($flags, self::FLAG_ARRAY)) {
                // This field has been flagged as "multi" which means
                // it contains data over several records. It needs to be handled
                // differently. We do, however, need the field to show up
                // to it triggers a call to __set() later on.
                $sqlFields[] = "NULL as `{$classMemberName}`";

            } elseif (self::isFlagSet($flags, self::FLAG_FILE)) {
                $sqlFields[] = "NULL as `{$classMemberName}`";

            } else {

                $sqlFields[] = sprintf('`%s`.`%s` as `%s`', $joinTableName, $databaseFieldName, $classMemberName);
            }
            $sqlJoins[] = sprintf('LEFT JOIN `tbl_entries_data_%d` AS `%s` ON `%2$s`.entry_id = e.id', $fieldId, $joinTableName);
        }

        return sprintf($sql, implode("," . PHP_EOL, $sqlFields), implode(PHP_EOL, $sqlJoins), self::getSectionId(), $where);
    }

    /**
     * Returns every entry in the section designated by the model.
     * @return ResultIterator An iterator of all results found. Each item in The
     *                        iterator is of the model type.
     */
    public static function all()
    {
        self::findSectionFields();
        $db = SymphonyPDO\Loader::instance();
        $query = $db->prepare(static::fetchSQL());
        $query->execute();

        return (new SymphonyPDO\Lib\ResultIterator(get_called_class(), $query));
    }

    /**
     * Retrieves a specific entry
     * @param  integer $entryId The specific entry id to look up
     * @return mixed            Returns the first item found. The type is
     *                          the same as The calling class.
     */
    public static function loadFromId($entryId)
    {
        self::findSectionFields();
        $db = SymphonyPDO\Loader::instance();
        $query = $db->prepare(static::fetchSQL('e.id = :id').' LIMIT 1');
        $query->bindValue(':id', $entryId, \PDO::PARAM_INT);
        $query->execute();

        return (new SymphonyPDO\Lib\ResultIterator(get_called_class(), $query))->current();
    }

    /**
     * This method will return the auto-generated join name for a given field
     * handle. It is used when generating the fetchSQL.
     * @param  string $handle Field handle
     * @return string         The internal join table name
     */
    protected static function findJoinTableFieldName($handle) {
        return static::$fieldMapping[$handle]['joinTableName'];
    }

    /**
     * Overload this method to set your own custom field mappings.
     * @return array The array of mappings
     * @usedby populateFieldMapping
     */
    protected static function getCustomFieldMapping() {
        return [];
    }

    /**
     * Given a classMemberName value, this method will return any field mapping
     * @return array The array for that field mapping
     */
     protected static function findCustomFieldMapping($classMemberName) {
         foreach(static::$fieldMapping as $field) {
            if($field['classMemberName'] == $classMemberName) {
                return $field;
            }
         }
         return [];
     }

    /**
     * This method populates the $sectionFields arrays
     * @return void
     */
    private static function populateFieldMapping() {

        // Look for any custom field mappings the model might be providing
        static::$fieldMapping = static::getCustomFieldMapping();

        foreach(static::$sectionFields as $handle => $id) {

            if(!isset(static::$fieldMapping[$handle])) {
                static::$fieldMapping[$handle] = [];
            }

            static::$fieldMapping[$handle]['fieldId'] = $id;

            if(!isset(static::$fieldMapping[$handle]['databaseFieldName'])) {
                static::$fieldMapping[$handle]['databaseFieldName'] = "value";
            }

            if(!isset(static::$fieldMapping[$handle]['classMemberName'])) {
                static::$fieldMapping[$handle]['classMemberName'] = self::handleToClassMemberName($handle);
            }

            // Generate a random name to use when joining tables. It will be
            // in the fetchSQL method. e.g. LEFT JOIN `tbl_entries_data_34` as `a23def3g2`
            static::$fieldMapping[$handle]['joinTableName'] = chr(rand(97, 122)).substr(md5($handle), 0, 8);
        }
    }

    /**
     * This will populate the $sectionFields array with field data. The end
     * result is a field element name to id mapping. e.g. 'first-name' => 23
     * @param  boolean $populateFieldMapping When true, this will call
     *                                       populateFieldMapping()
     * @return array                         The array of field element name to
     *                                       id mapping
     */
    protected static function findSectionFields($populateFieldMapping = true)
    {

        if (isset(static::$sectionFields) && !empty(static::$sectionFields)) {
            return static::$sectionFields;
        }

        $db = SymphonyPDO\Loader::instance();
        $query = $db->prepare(
            "SELECT `id`, `element_name` FROM `tbl_fields` WHERE `parent_section` = :sectionid"
        );

        if (false == $query->execute([':sectionid' => self::getSectionId()])) {
            throw new \Exception("No fields found for section with id `".self::getSectionId()."` !");
        }

        $fields = [];
        foreach ($query->fetchAll(\PDO::FETCH_ASSOC) as $field) {
            $fields[$field['element_name']] = $field['id'];
        }

        static::$sectionFields = $fields;

        // Generally we always want to update the field mappings
        if($populateFieldMapping == true) {
            static::populateFieldMapping();
        }

        return static::$sectionFields;
    }

    /**
     * Finds the ID of the section the child model is using.
     * @return integer The section ID
     */
    protected static function getSectionId()
    {
        return SectionManager::fetchIDFromHandle(
            static::getSectionHandleFromClassName()
        );
    }

    /**
     * Deletes this entry from the Symphony database
     * @return boolean Returns true on sucess, false on failure.
     */
    public function delete()
    {
        return EntryManager::delete([$this->id()]);
    }

    /**
     * Saves an entry
     * @param  string $sectionHandle Change which section this saves to
     * @return mixed                 Returns $this instance
     */
    public function save($sectionHandle=null)
    {
        if (is_null($sectionHandle)) {
            $sectionHandle = static::getSectionHandleFromClassName();
        }

        // This is a memory hog. Disable logging.
        Symphony::Database()->disableLogging();

        if (!is_null($this->id)) {
            $this->update($this->getData());
        } else {
            $this->id = $this->create($this->getData(), $sectionHandle);
        }

        // Reset the modified flag
        $this->flagAsNotModified();

        return $this;
    }

    /**
     * Uses Symphony's EntryManager to create a new entry
     * @param  array  $fields  The data to save. This is generally provided by
     *                         	getData()
     * @param  string $section Handle of the section the the entry is saved to.
     * @return mixed          Entry ID on success or false on failure.
     */
    protected function create(array $fields, $section)
    {
        $errors = [];
        $entry = EntryManager::create();
        $entry->set('section_id', SectionManager::fetchIDFromHandle($section));
        $entry->setDataFromPost($fields, $errors);

        $entry->commit();
        $result = (count($errors) > 0 ? false : $entry->get('id'));

        // Cleanup
        unset($entry);

        return $result;
    }

    /**
     * Uses Symphony's EntryManager to update a new entry. if there has not been
     * and modification (i.e. hasBeenModified() returns false), this method will
     * throw an exception.
     * @param  array  $fields The data to save. This is generally provided by
     *                        	getData()
     * @return mixed          Entry ID on success or false on failure.
     * @throws ModelHasNotBeenModifiedException
     */
    protected function update(array $fields)
    {
        if (!$this->hasBeenModified()) {
            throw new Exceptions\ModelHasNotBeenModifiedException("The Entry has not been modified. Unable to save.");
        }

        $errors = [];
        if(is_null($this->id) || (int)$this->id <= 0) {
            throw new Exception("No entry ID has been set. Unable to update.");
        }

        $entry = EntryManager::fetch($this->id)[0];
        if(!($entry instanceof Entry)) {
            throw new Exceptions\ModelEntryNotFoundException("Entry with id {$this->id} could not be located.");
        }

        $entry->setDataFromPost($fields, $errors);

        $entry->commit();
        $result = (count($errors) > 0 ? false : $entry->get('id'));

        // Cleanup
        unset($entry);

        return $result;
    }

    /**
     * Getter method. Allows retrival of properties in the $properties array
     * @param  string $name The name of the property to return
     * @return mixed       The value of the property.
     */
    public function __get($name)
    {
        return $this->properties[$name];
    }

    /**
     * Setter method. Allows setting of properties. Any property set this way
     * will cause $hasBeenModified to toggle to true.
     * @param string $name  Name of the property to set
     * @param mixed $value The value to assign to this property
     */
    public function __set($name, $value)
    {

        // Need to check to see if this field mapping has any flags associated
        // e.g. 'multi'. We ONLY do this if the field has not already been
        // modified. We know this by seeing if the calling method was from
        // the PDO Database ResultIterator
        if($this->getCallingMethod() == 'fetch') {
            $fieldMapping = static::findCustomFieldMapping($name);
            if(isset($fieldMapping['flags'])){

                $flags = $fieldMapping['flags'];

                // Files have extra, useful, fields like size and context
                // specific metadata. We need to retain that information
                // if the FLAG_FILE flag is set.
                if(self::isFlagSet($flags, self::FLAG_FILE)) {
                    $databaseFieldName = $fieldMapping['databaseFieldName'];
                    $classMemberName = $fieldMapping['classMemberName'];
                    $joinTableName = $fieldMapping['joinTableName'];
                    $fieldId = $fieldMapping['fieldId'];

                    $db = SymphonyPDO\Loader::instance();
                    $query = $db->prepare(sprintf(
                        "SELECT %s.file, %1\$s.size, %1\$s.mimetype, %1\$s.meta
                        FROM `tbl_entries_data_%d` AS `%1\$s`
                        WHERE `%1\$s`.entry_id = :id LIMIT 1",

                        $joinTableName,
                        (int)$fieldId
                    ));
                    $query->bindValue(':id', $this->id, \PDO::PARAM_INT);
                    $result = $query->execute();
                    $value = $query->fetch(\PDO::FETCH_ASSOC);
                }

                // Some fields can have multiple rows of data, e.g. select box
                // link, tag, or mulit-select fields. This pulls out the data
                // as a set and assigns it as an array rather than a
                // basic string value.
                if(self::isFlagSet($flags, self::FLAG_ARRAY)) {
                    $databaseFieldName = $fieldMapping['databaseFieldName'];
                    $classMemberName = $fieldMapping['classMemberName'];
                    $joinTableName = $fieldMapping['joinTableName'];
                    $fieldId = $fieldMapping['fieldId'];

                    $db = SymphonyPDO\Loader::instance();
                    $query = $db->prepare(sprintf(
                        "SELECT SQL_CALC_FOUND_ROWS `%s`.`%s` as `%s`
                        FROM `tbl_entries_data_%d` AS `%1\$s`
                        WHERE `%1\$s`.entry_id = :id AND `%1\$s`.`%2\$s` IS NOT NULL",

                        $joinTableName,
                        $databaseFieldName,
                        $classMemberName,
                        (int)$fieldId
                    ));
                    $query->bindValue(':id', $this->id, \PDO::PARAM_INT);
                    $result = $query->execute();
                    $value = $query->fetchAll(\PDO::FETCH_COLUMN);

                    // FLAG_ARRAY supports some of the type flags. Run through
                    // and see if any are set. Apply the type conversion to all
                    // items in the array.
                    if(self::isFlagSet($flags, self::FLAG_BOOL)) {
                        $func = function($input){
                            return (strtolower($value) == 'yes' || $value === true);
                        };
                        $value = array_map($func, $value);

                    } elseif(self::isFlagSet($flags, self::FLAG_INT)) {
                        $value = array_map('intval', $value);

                    } elseif(self::isFlagSet($flags, self::FLAG_STR)) {
                        $value = array_map('strval', $value);

                    } elseif(self::isFlagSet($flags, self::FLAG_FLOAT)) {
                        $value = array_map('floatval', $value);

                    } elseif(self::isFlagSet($flags, self::FLAG_CURRENCY)) {
                        $func = function($input){
                            return (float)number_format((float)$value, 2, NULL, NULL);
                        };
                        $value = array_map($func, $value);
                    }

                // If FLAG_ARRAY isn't set, we still need to check for type
                // flags. Apply the type conversion to the value. Note, it
                // doesn't make sense to combine these flags. e.g.
                // FLAG_BOOL | FLAG_CURRENCY so just assume one is only ever
                // set.
                } elseif(self::isFlagSet($flags, self::FLAG_BOOL)) {
                    $value = (strtolower($value) == 'yes' || $value === true);

                } elseif(self::isFlagSet($flags, self::FLAG_INT)) {
                    $value = (int)$value;

                } elseif(self::isFlagSet($flags, self::FLAG_STR)) {
                    $value = (string)$value;

                } elseif(self::isFlagSet($flags, self::FLAG_FLOAT)) {
                    $value = (float)$value;

                } elseif(self::isFlagSet($flags, self::FLAG_CURRENCY)) {
                    $value = (float)number_format((float)$value, 2, NULL, NULL);
                }

                // If the FLAG_NULL flag is set, we need to convert empty values
                // i.e. int(0), string(""), (array)[], into a NULL. FLAG_ARRAY
                // supports combining with FLAG_NULL.
                if(self::isFlagSet($flags, self::FLAG_NULL)) {
                    if(is_array($value) && !empty($value)) {
                        $func = function($input){
                            return empty($input) ? NULL : $input;
                        };
                        $value = array_map($func, $value);
                    } else {
                        $value = empty($value) ? NULL : $value;
                    }
                }
            }
        }

        // Only set the modified flag if this is an exsiting entry, and the
        // value has actually changed.
        // #1 - This won't trigger if the class is being initialised
        // automatically by PDOStatement fetch().
        if (!is_null($this->id) && $this->properties[$name] != $value && $this->getCaller() != "PDOStatement::fetch") {
            $this->flagAsModified();
        }

        $this->properties[$name] = $value;
    }

    /**
     * Magic method. Do not call explicitly. Alternative to the __get and __set
     * method. E.g. $this->firstName() is the same as $this->firstName.
     * Similarly, $this->firstName = "bob" is the same as $this->firstName("bob")
     * @param  string $name The name of the property to operate on
     * @param  array $args An index array with a single item. The contents of
     *                     	index 0 will be saved against the property $name
     * @return mixed       Returns $this instance for method chaining
     */
    public function __call($name, $args)
    {
        if (empty($args)) {
            return $this->$name;
        }

        $this->$name = $args[0];
        return $this;
    }

    /**
     * Sets $hasBeenModified to true
     * @return void
     */
    public function flagAsModified()
    {
        $this->hasBeenModified = true;
    }

    /**
     * Sets $hasBeenModified to false
     * @return void
     */
    public function flagAsNotModified()
    {
        $this->hasBeenModified = false;
    }

    /**
     * Get the hasBeenModified flag.
     * @return boolean Returns the $hasBeenModified flag
     */
    public function hasBeenModified()
    {
        return $this->hasBeenModified;
    }

    /**
     * Different to getData(), this will return the raw $properties array
     * @return array Key/Value pairs of data stored in this object
     */
    public function toArray()
    {
        return $this->properties;
    }

    /**
     * This utility method will examine the backtrace and return the method
     * that made the call to the method that is using this.
     * @param   Integer $depth  How far into the backtrace we should look.
     *                          The default is 2, which is this method, the
     *                          calling method, and the method just before that.
     * @return  String the name of the method.
     */
    private function getCallingMethod($depth = 2) {
        return debug_backtrace()[$depth]['function'];
    }

    /**
     * This utility method will examine the backtrace and return the class
     * that made the call to the method that is using this.
     * @param   Integer $depth  How far into the backtrace we should look.
     *                          The default is 2, which is this method, the
     *                          calling method, and the method just before that.
     * @return  String the name of the method.
     */
    private function getCallingClass($depth = 2) {
        return debug_backtrace()[$depth]['class'];
    }

    /**
     * This convienence method that combines getCallingClass & getCallingMethod
     * to return a single string delimited by a double colon.
     * @param   Integer $depth  How far into the backtrace we should look.
     *                          The default is 2, which is this method, the
     *                          calling method, and the method just before that.
     * @return  String the name of the method.
     */
    private function getCaller($depth = 2) {
        // Important: Add 1 more to the depth since this goes one level deeper
        // by calling getCallingClass() and getCallingMethod()
        return sprintf("%s::%s", $this->getCallingClass($depth + 1), $this->getCallingMethod($depth + 1));
    }
}
