<?php

namespace PavolEichler\Database;

/**
 * Base class for an object providing access to data stored in a single SQL table.
 *
 * @author Pavol Eichler <pavol.eichler@gmail.com>
 */
abstract class Table extends Database
{

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *                               Identifiers
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /**
     * Main table. Pass to object methods when not sure about the main table name.
     */
    const TABLE_MAIN = null;
    /*
     * Column array key storing the table name.
     */
    const TABLE_NAME = '0';
    /**
     * Column array key storing the column name.
     */
    const COLUMN_NAME = '1';
    /**
     * Complete column identifier.
     */
    const COLUMN_SQL_IDENTIFIER = 'sql';

    const FORMAT_NAME = false;
    const FORMAT_ALIAS = 2;
    const FORMAT_ALIASED_NAME = true;
    
    
    /**
     * Main table key => name pair.
     * @var array
     */
    protected $table;

    /**
     * Autoincrement column name. Set as null, if no autoincrement column exists.
     * @var string
     */
    protected $autoIncrement = 'id';

    /**
     *
     * @param \Dibi\Connection $dibi
     * @param \Nette\Caching\Cache $cache
     * @param array $table
     */
    public function __construct(\Dibi\Connection $dibi, $table = null){
        parent::__construct($dibi);

        // if no table name provided, guess it from the class name
        $this->setTable($table === null ? self::camelCaseToUnderscore($this->getReflection()->getShortName()) : $table);

    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                               Configuration
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Public table name getter.
     *
     * @return array
     */
    public function getTable() {

        return $this->table;

    }

    /**
     * Public table name setter.
     *
     * @param string|array $table
     */
    public function setTable($table) {

        $this->table = is_array($table) ? $table : array(self::camelCaseToUnderscore($this->getReflection()->getShortName()) => $table);

    }

    /**
     * Public autoincrement column name getter.
     *
     * @return string
     */
    public function getAutoIncrementColumn() {

        return $this->autoIncrement;

    }

    /**
     * Public autoincrement column name setter.
     *
     * @param string $autoincrement
     */
    public function setAutoIncrementColumn($autoIncrement) {

        $this->autoIncrement = $autoIncrement;

    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                                 Tools
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Gets the name of a table by its key.
     *
     * @param string $key
     * @param bool|string $alias Whether to alias the table name or not. Defaults to true. If a string is provided, the passed value will be used as the table alias.
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function table($key = self::TABLE_MAIN, $alias = self::FORMAT_ALIASED_NAME) {

        // if no table name is specified, return the main table
        if ($key === self::TABLE_MAIN OR isset($this->table[$key])){
            if ($alias === self::FORMAT_ALIASED_NAME)
                return array(reset($this->table) => key($this->table));
            elseif ($alias === self::FORMAT_NAME)
                return reset($this->table);
            elseif ($alias === self::FORMAT_ALIAS)
                return key($this->table);
            elseif (is_string ($alias))
                return array(reset($this->table) => $alias);
            else
                throw new \InvalidArgumentException('Invalid alias format, be sure to use one of the class constants.');
        }

        // any other table
        if ($alias === self::FORMAT_ALIASED_NAME)
            return array($key  => $key);
        elseif ($alias === self::FORMAT_NAME)
            return $key;
        elseif ($alias === self::FORMAT_ALIAS)
            return $key;
        elseif (is_string ($alias))
            return array($key => $alias);
        else
            throw new \InvalidArgumentException('Invalid alias format, be sure to use one of the class constants.');
        
    }

    /**
     * Returns a column identifier string, specific enough to prevent ambigous resolution.
     * For columns with no table name specified, the main table alias is prepended.
     *
     * @param string|array $column Column name or an array with 2 keys - table name and column name.
     * @param mixxed $type Type of the column identifier to return.
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function column($column, $type = self::COLUMN_SQL_IDENTIFIER) {

        // validate the column format
        if (is_array($column) AND (!array_key_exists(self::TABLE_NAME, $column) OR !array_key_exists(self::COLUMN_NAME, $column)))
            throw new \InvalidArgumentException('Invalid column format. Expecting an array with two keys.');

        switch ($type) {
            case self::COLUMN_SQL_IDENTIFIER:
                // [table].[column] format
                return is_array($column) ?
                    $column[self::TABLE_NAME] . '.' . $column[self::COLUMN_NAME] :
                    key($this->table) . '.' . $column;
            case self::TABLE_NAME:
                // table name only
                return is_array($column) ?
                    $column[self::TABLE_NAME] :
                    key($this->table);
            case self::COLUMN_NAME:
                // column name only
                return is_array($column) ?
                    $column[self::COLUMN_NAME] :
                    $column;
            default:
                // unknown type
                throw new \InvalidArgumentException('Invalid type, be sure to use one of the class constants.');
                break;
        }

    }

    /**
     * Fetches all rows.
     *
     * @param \Dibi\Fluent $fluent
     * @return \Dibi\Result The returned rows.
     * @throws \Exception
     */
    protected function fetchAll(\Dibi\Fluent $fluent) {

        // simply fetch data
        $result =  $this->fetch($fluent);

        // return data as a DibiRowCollection
        return DibiRowCollection::from($result);

    }

    /**
     * Fetches one row.
     *
     * @param \Dibi\Fluent $fluent
     * @return \Dibi\Row The returned row.
     */
    protected function fetchOne(\Dibi\Fluent $fluent) {

        // fetch all data and return the first row
        $rows = $this->fetchAll($fluent);

        return $rows ? reset($rows) : $rows;

    }

}