<?php

namespace Zaraguza\Database;

/**
 * Closure tree master table service.
 * 
 * @author Pavol Eichler <pavol.eichler@gmail.com>
 */
abstract class ClosureTreeMasterTable extends JoinedTable
{

    /**
     * Slave table.
     */
    const TABLE_SLAVE = 'slave';

    /**
     * Closure tree service.
     * @var ClosureTreeSlaveTable
     */
    protected $tree;

    /**
     *
     * @param \DibiConnection $dibi
     * @param SingleTable $tree
     * @param \Nette\Caching\Cache $cache
     * @param array $table
     */
    public function __construct(ClosureTreeSlaveTable $tree, \DibiConnection $dibi, \Nette\Caching\Cache $cache = null, $table = null){
        
        $this->tree = $tree;
        
        parent::__construct($dibi, $cache, $table);
        
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                               Configuration
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Public tree service getter.
     *
     * @return SingleTable
     */
    public function getTree() {

        return $this->tree;

    }

    /**
     * Public tree service setter.
     *
     * @param SingleTable $tree
     */
    public function setTree(SingleTable $tree) {

        $this->tree = $tree;

    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                             Model behaviour
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Formats the basic select. Do not call it yourself, use select() instead.
     *
     * @return \DibiFluent
     */
    protected function base() {
        
        return $this->dibi->select('%n.*, %n.%n',
                            $this->table(self::TABLE_MAIN, self::FORMAT_ALIAS),
                            $this->table(self::TABLE_SLAVE, self::FORMAT_ALIAS), $this->tree->getAncestorColumn())
                    ->from($this->table(self::TABLE_MAIN))
                    ->leftJoin($this->table(self::TABLE_SLAVE))
                    ->on("%n.%n = %n.id AND %n.%n = 1",
                         $this->table(self::TABLE_SLAVE, self::FORMAT_ALIAS), $this->tree->getDescendantColumn(),
                         $this->table(self::TABLE_MAIN, self::FORMAT_ALIAS),
                         $this->table(self::TABLE_SLAVE, self::FORMAT_ALIAS), $this->tree->getDistanceColumn());

    }

    /**
     * Fetches the rows in a given format.
     *
     * @param \DibiFluent $fluent
     * @return \DibiResult The returned rows.
     */
    protected function fetch(\DibiFluent $fluent) {

        return $fluent->fetchAll();

    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                               Adding rows
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */


    /**
     * Inserts a row to the table.
     *
     * @param array $values Values for the row to be inserted. May be an array of arrays for a multi insert.
     * @param array $columns An array of allowed columns to be specified in the insert.
     * @param string $return The expected return value, use one of the class constants (RETURN_ROW_COUNT, RETURN_AFFECTED_ROWS). RETURN_AFFECTED_ROWS is supported for single row inserts only.
     * @return \DibiRow|int Based on the value of $return argument, the count of inserted rows or the row itself.
     */
    public function add($values, $return = self::RETURN_ROW_COUNT, $columns = null) {

        // validate arguments
        if (count($values) AND !self::isAssociativeArray($values)){
            throw new \InvalidArgumentException('Multiple row inserts are not allowed with closure tree tables.');
        }
        
        // manage parent value
        // extract the new parent, if specified
        $parent = isset($values['parent']) ? 
                        $values['parent'] : 
                        (isset($values['parent_id']) ? 
                            $values['parent_id'] :
                            null);
        
        // remove the parent from values
        $values = array_diff_key($values, array('parent_id' => null, 'parent' => null));

        // add a row to the master table
        $row = parent::add($values, self::RETURN_AFFECTED_ROWS, $columns);

        // get the last insert ID
        $id = $row->{$this->autoIncrement};

        // create a correct tree structure in the slave table
        $this->tree->add($parent, $id);

        // invalidate cache
        $this->invalidateCache();

        // return value
        return ($return === self::RETURN_AFFECTED_ROWS) ? $this->oneByAutoIncrement($row->{$this->autoIncrement}) : count($row);

    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                              Modyfing rows
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /**
     * Update rows with a given column values.
     *
     * @param string|array $property Property name or an array with 2 keys - a table name and a column name.
     * @param string $condition Condition type. Use one of the class constants.
     * @param mixed $value Value to choose rows.
     * @param array $values Values to update the row to.
     * @param string $return The expected return value, use one of the class constants (RETURN_ROW_COUNT, RETURN_AFFECTED_ROWS). RETURN_AFFECTED_ROWS is supported for single row inserts only.
     * @param array $columns Columns to update.
     * @return int The number of affected rows.
     * @throws \InvalidArgumentException
     */

    public function modifyBy($property, $condition, $value, $values, $return = self::RETURN_ROW_COUNT, $columns = null) {
        
        // extract the parent or parent ID, if specified
        $updateParent = false;
        if (isset($values['parent']) OR isset($values['parent_id'])){
            $updateParent = true;
            // get the new parent ID value
            $parent = isset($values['parent']) ? $values['parent'] : $values['parent_id'];
            $parent = ($parent instanceof \DibiRow) ? $parent->id : $parent;
            // remove the parent from values
            $values = array_diff_key(array_keys($values), array('parent_id' => null, 'parent' => null));
        }

        // execute the actual update
        parent::modifyBy($property, $condition, $value, $values, $return, $columns);
        
        // update the slave table, if the parent was specified
        if ($updateParent){
            // get all affected IDs
            $select = $this->applyConditions($this->select('id'), $property, $condition, $value);
            $ids = $select->fetchAll();
            // update them all
            foreach($ids as $child){
                $this->tree->modify($parent, $child);
            }
        }
        
        return $result;
        
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                              Deleting rows
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Remove rows with a given column value.
     *
     * @param string|array $property Property name or an array with 2 keys - a table name and a column name.
     * @param string $condition Condition type. Use one of the class constants.
     * @param mixed $value Value.
     * @return int The number of rows removed.
     * @throws \InvalidArgumentException
     */
    public function removeBy($property, $condition, $value) {
        
        // get all affected IDs
        $select = $this->applyConditions($this->select('id'), $property, $condition, $value);
        $ids = $select->fetchAll();
        
        // get all descendants and self
        $descendants = $this->getByAncestor($ids);
        
        // remove all subtree items in the main table
        $this->dibi->delete($this->table())->where('id %in', $descendants);
        
        // remove all related data in the slave table
        foreach($ids as $id){
            $this->tree->remove($id);
        }
        
        // return the count of affected rows
        return count($descendants);
        
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                              Conditions
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    
    /**
     * Apply an ancestor condition.
     * 
     * @param \DibiFluent $fluent
     * @param string $condition One of the class constants.
     * @param mixed $value
     * @return \DibiFluent
     * @throws \Exception
     */
    protected function applyAncestorCondition(\DibiFluent $fluent, $condition, $value) {

        // support DibiRow objects
        if ($value instanceof \DibiRow){
            $value = $value->id;
        }
        
        // validate arguments
        if ($condition != self::IS_EQUAL)
            throw new \InvalidArgumentException('IS_EQUAL is the only supported condition with child.');
        
        // join the tree table
        $fluent->join($this->table(self::TABLE_SLAVE, '_parenttree'))
               ->on('_parenttree.%n = %n.id',
                    $this->tree->getDescendantColumn(),
                    $this->table(self::TABLE_MASTER, self::FORMAT_ALIAS));
        
        // apply conditions
        // require descendants only
        $fluent = $this->applyCondition($fluent, array('_parenttree', $this->tree->getAncestorColumn()), $condition, $value);
        // require distance != 0
        $fluent = $this->whereNotEqual($fluent, array('_parenttree', $this->tree->getDistanceColumn()), 0);

        return $fluent;
        
    }
    
    /**
     * Apply a descendant condition.
     * 
     * @param \DibiFluent $fluent
     * @param string $condition One of the class constants.
     * @param mixed $value
     * @return \DibiFluent
     * @throws \Exception
     */
    protected function applyDescendantCondition(\DibiFluent $fluent, $condition, $value) {

        // support DibiRow objects
        if ($value instanceof \DibiRow){
            $value = $value->id;
        }
        
        // validate arguments
        if ($condition != self::IS_EQUAL)
            throw new \InvalidArgumentException('IS_EQUAL is the only supported condition with child.');
        
        // join the tree table
        $fluent->join($this->table(self::TABLE_SLAVE, '_childtree'))
               ->on('_childtree.%n = %n.id',
                    $this->tree->getAncestorColumn(),
                    $this->table(self::TABLE_MASTER, self::FORMAT_ALIAS));
        
        // apply conditions
        // require ancestors only
        $fluent = $this->applyCondition($fluent, array('_childtree', $this->tree->getDescendantColumn()), $condition, $value);
        // require distance != 0
        $fluent = $this->whereNotEqual($fluent, array('_childtree', $this->tree->getDistanceColumn()), 0);

        return $fluent;
        
    }
    
    /**
     * Apply a parent condition.
     * 
     * @param \DibiFluent $fluent
     * @param string $condition One of the class constants.
     * @param mixed $value
     * @return \DibiFluent
     * @throws \Exception
     */
    protected function applyParentCondition(\DibiFluent $fluent, $condition, $value) {
        
        // support DibiRow objects
        if ($value instanceof \DibiRow){
            $value = $value->id;
        }
        
        // validate arguments
        if ($condition != self::IS_EQUAL)
            throw new \InvalidArgumentException('IS_EQUAL is the only supported condition with parent.');
        
        $fluent = $this->applyCondition($fluent, array($this->table(self::TABLE_SLAVE, self::FORMAT_ALIAS), $this->tree->getAncestorColumn()), $condition, $value);
        
        return $fluent;
        
    }
    
    /**
     * Apply a child condition.
     * 
     * @param \DibiFluent $fluent
     * @param string $condition One of the class constants.
     * @param mixed $value
     * @return \DibiFluent
     * @throws \InvalidArgumentException
     */
    protected function applyChildCondition(\DibiFluent $fluent, $condition, $value) {

        // support DibiRow objects
        if ($value instanceof \DibiRow){
            $value = $value->id;
        }
        
        // validate arguments
        if ($condition != self::IS_EQUAL)
            throw new \InvalidArgumentException('IS_EQUAL is the only supported condition with child.');
        
        // join the tree table
        $fluent->join($this->table(self::TABLE_SLAVE, '_childtree'))
               ->on('_childtree.%n = %n.id',
                    $this->tree->getAncestorColumn(),
                    $this->table(self::TABLE_MASTER, self::FORMAT_ALIAS));
        
        // apply conditions
        // require ancestors only
        $fluent = $this->applyCondition($fluent, array('_childtree', $this->tree->getDescendantColumn()), $condition, $value);
        // require distance = 1
        $fluent = $this->whereEqual($fluent, array('_childtree', $this->tree->getDistanceColumn()), 1);

        return $fluent;
        
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
        if ($key === self::TABLE_SLAVE){
            if ($alias === self::FORMAT_ALIASED_NAME){
                return array($this->table(self::TABLE_SLAVE, self::FORMAT_NAME) => $this->table(self::TABLE_SLAVE, self::FORMAT_ALIAS));
            }elseif ($alias === self::FORMAT_NAME){
                // get the table name from the slave tree object
                $table = $this->tree->getTable();
                return reset($table);
            }elseif ($alias === self::FORMAT_ALIAS){
                // get the table aliast from the slave tree object
                $table = $this->tree->getTable();
                return key($table);
            }elseif (is_string ($alias)){
                return $this->table(self::TABLE_SLAVE, self::FORMAT_NAME) . ' ' . $alias;
            }
        }
        
        return parent::table($key = self::TABLE_MAIN, $alias);
        
    }
    
}