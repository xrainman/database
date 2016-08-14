<?php

namespace PavolEichler\Database;

/**
 * Base class for an object providing access to data stored in several SQL tables.
 * Each fetched row is supposed to describe one item.
 *
 * @author Pavol Eichler <pavol.eichler@gmail.com>
 */
abstract class JoinedTable extends SingleTable
{

    /**
     * Join table aliases and real table names.
     * 
     * @var array
     */
    protected $joins = array();

    /**
     *
     * @param \Dibi\Connection $dibi
     * @param \Nette\Caching\Cache $cache
     * @param array $table
     * @param array $joins
     * @throws \Exception
     */
    public function __construct(\Dibi\Connection $dibi, \Nette\Caching\Cache $cache = null, $table = null, array $joins = array()) {
        parent::__construct($dibi, $cache, $table);

        if (count($this->table) != 1)
            throw new \Exception('The main $table should be specified as an array with one alias => real name element.');

        // join table names translation table
        $this->joins = $joins;

    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                               Configuration
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Public join table real name getter.
     *
     * @param type $key Internal table name.
     * @return string
     */
    public function getJoinTableRealName($key) {

        return isset($this->joins[$key]) ? $this->joins[$key] : null;

    }

    /**
     * Public join table real name setter.
     *
     * @param type $key Internal table name. This should not change, even if the real table name does.
     * @param type $name Table name.
     */
    public function setJoinTableRealName($key, $name) {

        $this->joins[$key] = $name;

    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                               Tools
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Gets the name of a table by its key.
     *
     * @param string $key
     * @param bool|string $alias Whether to alias the table name or not. Defaults to true. If a string is provided, the passed value will be used as the table alias.
     * @return string
     * @throws \Exception
     */
    protected function table($key = self::TABLE_MAIN, $alias = self::FORMAT_ALIASED_NAME) {

        // table by name
        // join table, use translated name, if available
        // use joins table only if the main table does not match
        if (!isset($this->table[$key]) AND isset($this->joins[$key])){
            if ($alias === self::FORMAT_ALIASED_NAME)
                return array($this->joins[$key] => $key);
            elseif ($alias === self::FORMAT_NAME)
                return $this->joins[$key];
            elseif ($alias === self::FORMAT_ALIAS)
                return $key;
            elseif (is_string ($alias))
                return $this->joins[$key] . ' ' . $alias;
        }
        
        return parent::table($key, $alias);
        
    }

}