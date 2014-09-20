<?php

namespace Zaraguza\Database;

/**
 * Base class for an object providing access to data stored in a single SQL table.
 * Each fetched row is supposed to describe one item.
 *
 *
 * @method setDefault${Column}($value)
 * @method getDefault${Column}()
 * @method unsetDefault${Column}($value)
 * @method modifyBy${Column}($value, $values, $return = self::RETURN_ROW_COUNT, $columns = null)
 * @method modifyBy${Column}Not($value, $values, $return = self::RETURN_ROW_COUNT, $columns = null)
 * @method modifyBy${Column}Like($value, $values, $return = self::RETURN_ROW_COUNT, $columns = null)
 * @method modifyBy${Column}NotLike($value, $values, $return = self::RETURN_ROW_COUNT, $columns = null)
 * @method modifyBy${Column}GreaterThan($value, $values, $return = self::RETURN_ROW_COUNT, $columns = null)
 * @method modifyBy${Column}GreaterOrEqualThan($value, $values, $return = self::RETURN_ROW_COUNT, $columns = null)
 * @method modifyBy${Column}LowerThan($value, $values, $return = self::RETURN_ROW_COUNT, $columns = null)
 * @method modifyBy${Column}LowerOrEqualThan($value, $values, $return = self::RETURN_ROW_COUNT, $columns = null)
 * @method removeBy${Column}($value)
 * @method removeBy${Column}Not($value)
 * @method removeBy${Column}Like($value)
 * @method removeBy${Column}NotLike($value)
 * @method removeBy${Column}GreaterThan($value)
 * @method removeBy${Column}GreaterOrEqualThan($value)
 * @method removeBy${Column}LowerThan($value)
 * @method removeBy${Column}LowerOrEqualThan($value)
 * @method saveBy${Column}($value = null, $values, $return = self::RETURN_ROW_COUNT, $columns = null)
 *
 * @author Pavol Eichler <pavol.eichler@gmail.com>
 */
abstract class SingleTable extends CacheableTable
{


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * 
     *                              Return values
     * 
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /**
     * Affected rows count.
     */
    const RETURN_ROW_COUNT = 'count';
    /**
     * Actual affected rows, the whole data.
     */
    const RETURN_AFFECTED_ROWS = 'rows';

    /**
     * An array of default data values.
     * Allows to preset default values for added rows.
     * @var array
     */
    protected $defaults = array();


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                            Basic SQL commands
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Insert an array of rows into the main table.
     * 
     * @param array $values Array of rows.
     * @return Number of affected rows.
     */
    protected function insert($values) {
        
        // insert all row values
        $insert = array();
        $keys = null;
        foreach($values as $row) {
            // apply default values
            $row = array_merge($this->defaults, $row);
            // on the first iteration, load the columns being set
            if ($keys === null){
                $keys = array_keys($row);
                // initiate the insert array
                $insert = array_fill_keys($keys, array());
            }
            // convert the data format
            foreach($keys as $column){
                // check values integrity
                if (!key_exists($column, $row)){
                    throw new \InvalidArgumentException('The rows provided in the values array must all contain the same columns.');
                }
                // use the provided value if avilable, the default value otherwise
                $insert[$column][] = $row[$column];
            }
        }
        
        // no data to insert
        if (!count($insert) OR !count(reset($insert))){
            return 0;
        }

        // execute
        return $this->dibi->query('INSERT INTO %n %m', $this->table(self::TABLE_MAIN, false), $insert);

    }

    /**
     * Returns the basic update fluent for the main table.
     *
     * @param string $table
     * @param array $values
     * @return \DibiFluent
     */
    protected function update($values) {

        // get a new basic update dibi fluent
        $update = $this->dibi->update($this->table(self::TABLE_MAIN), $values);
        // apply all the filters
        $update = $this->applyFilters($update);

        return $update;

    }

    /**
     * Returns the basic delete fluent for the main table.
     *
     * @param string $table
     * @return \DibiFluent
     */
    protected function delete() {

        // get a new basic delete dibi fluent
        $delete = $this->dibi->delete($this->table(self::TABLE_MAIN));
        // set the main table as the only table to delete rows from
        // (allowing the following syntax: DELETE table FROM table JOIN ...)
        $delete->delete($this->table(self::TABLE_MAIN, self::FORMAT_ALIAS));
        
        // apply all the filters
        $delete = $this->applyFilters($delete);

        return $delete;

    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                             Default values
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Sets a default value for the given column.
     *
     * @param string|array $column
     * @param mixed $value
     * @throws \InvalidArgumentException
     */
    public function setDefault($column, $value) {

        if ($this->column($column, self::TABLE_NAME) != key($this->table))
            throw new \InvalidArgumentException('Default values are supported for main table columns only.');

        $this->defaults[$this->column($column, self::COLUMN_NAME)] = $value;

    }

    /**
     * Gets a default value for the given column.
     * This method returns the default value defined in the object by a setDefault method only. The default value of the column defined in the database may differ.
     *
     * @param string|array $column
     * @return mixed Returns the default value or null if not set.
     * @throws \InvalidArgumentException
     */
    public function getDefault($column) {

        if ($this->column($column, self::TABLE_NAME) != key($this->table))
            throw new \InvalidArgumentException('Default values are supported for main table columns only.');

        $key = $this->column($column, self::COLUMN_NAME);

        return isset($this->defaults[$key]) ? $this->defaults[$key] : null;

    }

    /**
     * Unsets a default value for the given column.
     *
     * @param string|array $column
     * @throws \InvalidArgumentException
     */
    public function unsetDefault($column) {

        if ($this->column($column, self::TABLE_NAME) != key($this->table))
            throw new \InvalidArgumentException('Default values are supported for main table columns only.');

        unset($this->defaults[$this->column($column, self::COLUMN_NAME)]);

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

        // invalid return value
        if ($return !== self::RETURN_AFFECTED_ROWS AND $return !== self::RETURN_ROW_COUNT){
            throw new \InvalidArgumentException('Unknown return value required.');
        }
        
        // check if we are performing a single or a multi insert and convert the values array to the correct format
        if (self::isAssociativeArray($values))
            $values = array($values);
        
        // process values in each row
        foreach($values as &$row) {
            // filter the provided values
            $row = $this->filterValues($row, $columns);
        }
        
        // execute the insert query
        $result = $this->insert($values);
        
        // invalidate cache
        $this->invalidateCache();
        
        // return value
        if ($return == self::RETURN_AFFECTED_ROWS){

            // check if we can satisfy this request
            if ($result > 1){
                throw new \InvalidArgumentException('RETURN_AFFECTED_ROWS is not supported when inserting multiple rows at once.');
            }elseif ($result === 0){
                return DibiRowCollection::from(array());
            }

            // get the last insert ID
            $id = $this->dibi->insertId();
            
            return $this->oneByAutoIncrement($id);
        
        }else{
            
            return $result;
        
        }

    }
    
    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                              Modyfing rows
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Update all rows.
     *
     * @param array $values Values to update the row to.
     * @param string $return The expected return value, use one of the class constants (RETURN_ROW_COUNT, RETURN_AFFECTED_ROWS). RETURN_AFFECTED_ROWS is supported for single row inserts only.
     * @param array $columns Columns to update.
     * @return int The number of affected rows.
     */
    public function modify($values, $return = self::RETURN_ROW_COUNT, $columns = null) {
        
        // update matching rows
        return $this->modifyBy(NULL, self::IS_ANY, NULL, $values, $return, $columns);
        
    }

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

        // validate arguments
        if ($return != self::RETURN_ROW_COUNT)
            throw new \InvalidArgumentException('RETURN_ROW_COUNT is the only supported return value when modifying rows.');

        // check if we have a method to satisfy the given condition
        if (!method_exists($this, $condition))
            throw new \InvalidArgumentException('Invalid condition provided. Please make sure you are using one of the class constants.');

        // filter the values to update, if requested
        if ($columns)
            $values = $this->filterValues ($values, $columns);

        // update matching rows
        $update = $this->update($values);
        $update = $this->applyConditions($update, $property, $condition, $value);

        // execute the update
        $result = $update->execute();
        
        // invalidate cache
        $this->invalidateCache();

        return $result;

    }

    /**
     * Update or insert a column with the given value.
     *
     * @param string|array $property Property name or an array with 2 keys - a table name and a column name.
     * @param mixed $value Value. If null, the row will be inserted.
     * @param array $values
     * @param string $return The expected return value, use one of the class constants (RETURN_ROW_COUNT, RETURN_AFFECTED_ROWS). RETURN_AFFECTED_ROWS is supported for single row inserts only.
     * @param array $columns
     * @return \DibiFluent|\DibiResult|int
     */
    public function saveBy($property, $value, $values, $return = self::RETURN_ROW_COUNT, $columns = null) {

        // validate arguments
        if ($return != self::RETURN_ROW_COUNT)
            throw new \InvalidArgumentException('RETURN_ROW_COUNT is the only supported return value when modifying rows.');
        
        // check if the row exists already
        $exists = $this->oneBy($property, self::IS_EQUAL, $value);
        
        if (!$exists){
            // the row does not exist yet, insert a new row
            return $this->add($values, $return, $columns);
        }else{
            // the row already exists, update the matching rows
            return $this->modifyBy($property, self::IS_EQUAL, $value, $values, $return, $columns);
        }

    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                              Deleting rows
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Delete all rows.
     *
     * @return int The number of rows removed.
     */
    public function remove() {

        return $this->removeBy(null, self::IS_ANY, null);

    }

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

        // check if we have a method to satisfy the given condition
        if (!method_exists($this, $condition))
            throw new \InvalidArgumentException('Invalid condition provided. Please make sure you are using one of the class constants.');

        // delete matching rows
        $delete = $this->delete();
        $delete = $this->applyConditions($delete, $property, $condition, $value);

        // execute the delete
        $result = $delete->execute();
        
        // invalidate cache
        $this->invalidateCache();

        return $result;

    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                              Magic
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    
    /**
     * Calls a correct method to execute the requested action. Used by the magic _call method.
     * Allows the object to be called with methods in the following format:
     * setDefault[ColumnName]($columnValue)
     * getDefault[ColumnName]()
     * unsetDefault[ColumnName]($columnValue)
     * setFilterBy[ColumnName][WhereCondition]($columnValue)
     * unsetFilterBy[ColumnName]()
     * only[ColumnName][WhereCondition]($columnValue)
     * any[ColumnName]()
     * countBy[ColumnName][WhereCondition]($columnValue)
     * countDistinctBy[ColumnName]($columnValue)
     * oneBy[ColumnName][WhereCondition]($columnValue, $order)
     * getBy[ColumnName][WhereCondition]($columnValue, $order, $limit = null, $offset = null)
     * modifyBy[ColumnName][WhereCondition]($columnValue, $values, $columnsToUpdate = null)
     * removeBy[ColumnName][WhereCondition]($columnValue)
     * saveBy[ColumnName]($columnValue = null, $values, $columnsToUpdate = null)
     * The [ColumnName] is a camel cased table column (with words separated with an underscore: '_'). The [WhereCondition] is one of the following:
     * Equal, NotEqual, Not, Like, NotLike, GreaterThan, GreaterOrEqualThan, NotGreaterThan, LowerThan, LowerOrEqualThan, NotLowerThan
     * 
     * @param string $action Action name.
     * @param array $column Column.
     * @param string $condition Condition.
     * @param string $args Other arguments to pass to the method.
     * @return mixed
     * @throws \Nette\MemberAccessException
     */
    protected function callMethod($action, $column, $condition, $args) {
        
        switch ($action) {

            case 'setDefault':

                // call a setDefault method
                array_unshift($args, $column);
                return call_user_func_array(array($this, 'setDefault'), $args);

            case 'getDefault':

                // call a getDefault method
                array_unshift($args, $column);
                return call_user_func_array(array($this, 'getDefault'), $args);

            case 'unsetDefault':

                // call an unsetDefault method
                array_unshift($args, $column);
                return call_user_func_array(array($this, 'unsetDefault'), $args);

            case 'modifyBy':

                // call an modifyBy method
                array_unshift($args, $column, $condition ?: self::IS_EQUAL);
                return call_user_func_array(array($this, 'modifyBy'), $args);

            case 'removeBy':

                // call a removeBy method
                array_unshift($args, $column, $condition ?: self::IS_EQUAL);
                return call_user_func_array(array($this, 'removeBy'), $args);

            case 'saveBy':

                // call a saveBy method
                array_unshift($args, $column);
                return call_user_func_array(array($this, 'saveBy'), $args);

            default:

                // let the parent decide
                return parent::callMethod($action, $column, $condition, $args);

        }
        
    }

    /**
     * Parses the magic method name.
     * 
     * @param string $name Method name.
     * @return array An array with max. 3 keys, the last being optional - action, column and condition.
     * @throws \Nette\MemberAccessException
     */
    protected function parseMethodName($name) {

        $valid = preg_match('/^(setDefault|getDefault|unsetDefault)([A-Za-z0-9]+?)(Equal|NotEqual|Not|Like|NotLike|GreaterThan|GreaterOrEqualThan|NotGreaterThan|LowerThan|LowerOrEqualThan|NotLowerThan)?$/', $name, $matches);
        // unknown method name
        if (!$valid)
            return parent::parseMethodName($name);

        return $matches;
        
    }

}