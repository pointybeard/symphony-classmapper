<?php

namespace Symphony\ClassMapper\Lib;

use \Symphony, \EntryManager, \SectionManager, \XMLElement;
use SymphonyPDO;
use Symphony\ClassMapper\Lib\Exceptions;

abstract class AbstractClassMapper
{
    protected $properties=[];

    // When update() is called, it will return immediately if nothing has been modified.

    protected $hasBeenModified = false;

    protected static function handleToClassMemberName($handle) {
        $bits = explode('-', $handle);
        $result = array_shift($bits);
        if(count($bits) > 0) {
            $result .= implode("", array_map('ucfirst', $bits));
        }
        return $result;
    }

    public function toXml()
    {
        $classname = array_pop(explode('\\', get_called_class()));
        $xml = new XMLElement($classname, NULL, ['id' => $this->id]);
        foreach(static::getData() as $key => $value) {
            $xml->appendChild(new XMLElement($key, \General::sanitize($value)));
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

    protected function getData()
    {
        $data = [];

        self::findSectionFields();

        foreach(static::$sectionFields as $fieldHandle => $fieldId) {
            $classMemberName = static::$fieldMapping[$fieldHandle]['classMemberName'];
            $data[$fieldHandle] = $this->$classMemberName;
        }

        return $data;
    }

    protected static function fetchSQL($where=1)
    {
        self::findSectionFields();

        $sql = "SELECT SQL_CALC_FOUND_ROWS %s, e.id as `id`
        FROM `tbl_entries` AS `e` %s
        WHERE e.section_id = %d AND %s
        ORDER BY e.id ASC";

        $sqlFields = $sqlJoins = [];

        foreach(static::$sectionFields as $fieldHandle => $fieldId) {

            $databaseFieldName = static::$fieldMapping[$fieldHandle]['databaseFieldName'];
            $classMemberName = static::$fieldMapping[$fieldHandle]['classMemberName'];
            $joinTableName = static::$fieldMapping[$fieldHandle]['joinTableName'];

            $sqlFields[] = sprintf('%s.%s as `%s`', $joinTableName, $databaseFieldName, $classMemberName);
            $sqlJoins[] = sprintf('LEFT JOIN `tbl_entries_data_%d` AS `%s` ON %2$s.entry_id = e.id', $fieldId, $joinTableName);
        }

        return sprintf($sql, implode("," . PHP_EOL, $sqlFields), implode(PHP_EOL, $sqlJoins), self::getSectionId(), $where);
    }


    public static function all()
    {
        self::findSectionFields();
        $db = SymphonyPDO\Loader::instance();
        $query = $db->prepare(static::fetchSQL());
        $query->execute();

        return (new SymphonyPDO\Lib\ResultIterator(get_called_class(), $query));
    }

    public static function loadFromId($entryId)
    {
        self::findSectionFields();
        $db = SymphonyPDO\Loader::instance();
        $query = $db->prepare(static::fetchSQL('e.id = :id').' LIMIT 1');
        $query->bindValue(':id', $entryId, \PDO::PARAM_INT);
        $query->execute();

        return (new SymphonyPDO\Lib\ResultIterator(get_called_class(), $query))->current();
    }

    protected static function findJoinTableFieldName($handle) {
        return static::$fieldMapping[$handle]['joinTableName'];
    }

    private static function populateFieldMapping() {

        foreach(static::$sectionFields as $handle => $id) {

            if(!isset(static::$fieldMapping[$handle])) {
                static::$fieldMapping[$handle] = [];
            }

            if(!isset(static::$fieldMapping[$handle]['databaseFieldName'])) {
                static::$fieldMapping[$handle]['databaseFieldName'] = "value";
            }

            if(!isset(static::$fieldMapping[$handle]['classMemberName'])) {
                static::$fieldMapping[$handle]['classMemberName'] = self::handleToClassMemberName($handle);
            }

            static::$fieldMapping[$handle]['joinTableName'] = chr(rand(97, 122)).substr(md5($handle), 0, 8);
        }
    }

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

        // We always want to update the field mappings
        self::populateFieldMapping();

        return static::$sectionFields;
    }

    protected static function getSectionId()
    {
        return SectionManager::fetchIDFromHandle(static::getSectionHandleFromClassName());
    }

    public function delete()
    {
        return EntryManager::delete([$this->id()]);
    }

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

    protected function update(array $fields)
    {
        if (!$this->hasBeenModified()) {
            throw new Exceptions\ModelHasNotBeenModified("The Entry has not been modified. Unable to save.");
        }

        $errors = [];
        $entry = EntryManager::fetch($this->id)[0];

        $entry->setDataFromPost($fields, $errors);

        $entry->commit();
        $result = (count($errors) > 0 ? false : $entry->get('id'));

        // Cleanup
        unset($entry);

        return $result;
    }

    protected function doInTransaction(\Closure $query)
    {
        $db = SymphonyPDO\Loader::instance();

        // Do everything in a try/catch so we can rollback the transaction if required.
        try {
            return $db->beginTransaction() && $query() && $db->commit();

        // If something went wrong, let's first catch it, rollback the transaction, and then throw the original
        // exception again for debugging.
        } catch (\Exception $ex) {
            $db->rollback();
            throw $ex;
        }
    }

    public function __get($name)
    {
        return $this->properties[$name];
    }

    public function __set($name, $value)
    {
        // Only set the modified flag if this is an exsiting entry, and the
        // value has actually changed.
        if (!is_null($this->id) && $this->properties[$name] != $value) {
            $this->flagAsModified();
        }
        $this->properties[$name] = $value;
    }

    public function __call($name, $args)
    {
        if (empty($args)) {
            return $this->$name;
        }

        $this->$name = $args[0];
        return $this;
    }

    public function flagAsModified()
    {
        $this->hasBeenModified = true;
    }

    public function flagAsNotModified()
    {
        $this->hasBeenModified = false;
    }

    public function hasBeenModified()
    {
        return $this->hasBeenModified;
    }

    public function toArray()
    {
        return $this->properties;
    }
}
