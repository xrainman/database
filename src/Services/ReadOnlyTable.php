<?php

namespace Zaraguza\Database;

/**
 * Base class for an object providing access to data stored in a single SQL table.
 * Each fetched row is supposed to describe one item.
 *
 *
 * @method setFilterBy${Column}($value)
 * @method setFilterBy${Column}Not($value)
 * @method setFilterBy${Column}Like($value)
 * @method setFilterBy${Column}NotLike($value)
 * @method setFilterBy${Column}GreaterThan($value)
 * @method setFilterBy${Column}GreaterOrEqualThan($value)
 * @method setFilterBy${Column}LowerThan($value)
 * @method setFilterBy${Column}LowerOrEqualThan($value)
 * @method unsetFilterBy${Column}()
 * @method only${Column}($value)
 * @method only${Column}Not($value)
 * @method only${Column}Like($value)
 * @method only${Column}NotLike($value)
 * @method only${Column}GreaterThan($value)
 * @method only${Column}GreaterOrEqualThan($value)
 * @method only${Column}LowerThan($value)
 * @method only${Column}LowerOrEqualThan($value)
 * @method any${Column}()
 * @method countBy${Column}($value)
 * @method countBy${Column}Not($value)
 * @method countBy${Column}Like($value)
 * @method countBy${Column}NotLike($value)
 * @method countBy${Column}GreaterThan($value)
 * @method countBy${Column}GreaterOrEqualThan($value)
 * @method countBy${Column}LowerThan($value)
 * @method countBy${Column}LowerOrEqualThan($value)
 * @method \DibiRow oneBy${Column}($value, $order = null)
 * @method \DibiRow oneBy${Column}Not($value, $order = null)
 * @method \DibiRow oneBy${Column}Like($value, $order = null)
 * @method \DibiRow oneBy${Column}NotLike($value, $order = null)
 * @method \DibiRow oneBy${Column}GreaterThan($value, $order = null)
 * @method \DibiRow oneBy${Column}GreaterOrEqualThan($value, $order = null)
 * @method \DibiRow oneBy${Column}LowerThan($value, $order = null)
 * @method \DibiRow oneBy${Column}LowerOrEqualThan($value, $order = null)
 * @method DibiRowCollection getBy${Column}($value, $order, $limit = null, $offset = null)
 * @method DibiRowCollection getBy${Column}Not($value, $order, $limit = null, $offset = null)
 * @method DibiRowCollection getBy${Column}Like($value, $order, $limit = null, $offset = null)
 * @method DibiRowCollection getBy${Column}NotLike($value, $order, $limit = null, $offset = null)
 * @method DibiRowCollection getBy${Column}GreaterThan($value, $order, $limit = null, $offset = null)
 * @method DibiRowCollection getBy${Column}GreaterOrEqualThan($value, $order, $limit = null, $offset = null)
 * @method DibiRowCollection getBy${Column}LowerThan($value, $order, $limit = null, $offset = null)
 * @method DibiRowCollection getBy${Column}LowerOrEqualThan($value, $order, $limit = null, $offset = null)
 *
 * @author Pavol Eichler <pavol.eichler@gmail.com>
 */
abstract class ReadOnlyTable extends Table
{

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                               Conditions
     * 
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    /**
     * No condition.
     */
    const IS_ANY = 'whereAny';
    /**
     * Equality condition.
     */
    const IS_EQUAL = 'whereEqual';
    /**
     * Unequality condition.
     */
    const IS_NOT_EQUAL = 'whereNotEqual';
    /**
     * Like condition.
     */
    const IS_LIKE = 'whereLike';
    /**
     * Unlike condition.
     */
    const IS_NOT_LIKE = 'whereNotLike';
    /**
     * Greater than condition.
     */
    const IS_GREATER_THAN = 'whereGreaterThan';
    /**
     * Greater or equal than condition.
     */
    const IS_GREATER_OR_EQUAL_THAN = 'whereGreaterOrEqualThan';
    /**
     * Lower than condition.
     */
    const IS_LOWER_THAN = 'whereLowerThan';
    /**
     * Greater or equal than condition.
     */
    const IS_LOWER_OR_EQUAL_THAN = 'whereLowerOrEqualThan';
    /**
     * Between condition.
     */
    const IS_BETWEEN = 'whereBetween';
    /**
     * Between or equal condition.
     */
    const IS_BETWEEN_OR_EQUAL = 'whereBetweenOrEqual';

    
    /**
     * An array of data filters.
     * Allows to restrict the rows which will be selected or modified by this object methods.
     * @var array
     */
    protected $filters = array();


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

        return $this->dibi->select('%n.*', $this->table(self::TABLE_MAIN, self::FORMAT_ALIAS))->from($this->table());

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
     *                            Basic SQL commands
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Returns the basic select fluent.
     * Call without arguments to retrieve the default fields, or pass custom fields in the same way, you would call the dibi::select() method.
     *
     * @return \DibiFluent
     */
    protected function select() {

        // get method arguments
        $args = func_get_args();

        $self = $this;
        $base = $this->adjustDibiBehaviour(null, function() use ($self) {
            
            // TODO call directly parent::method()
            // PHP 5.3 does not allow accessing protected methods for via $this keyword in an anonymous function
            // we will overcome this by calling the protected method through its reflection
            // for PHP 5.4, this could be replaced by simply callling '$this->method();'
            
            // get the reflection
            $method = new \Nette\Reflection\Method($self, 'base');
            // set the method as accessible
            $method->setAccessible(true);

            // call the method
            return $method->invoke($self);
            
        });
        
        
        // get a new basic select with all the filters applied
        $select = $this->applyFilters($base);

        if (count($args))
            // override the default select fields
            return call_user_func_array (array($select->select(false), 'select'), $args);
        else
            return $select;

    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                             Conditions
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Exlpodes and applies the provided condition to the fluent.
     * 
     * @param \DibiFluent $fluent
     * @param string|array $property
     * @param string $condition One of the class constants.
     * @param mixed $value
     * @return type
     */
    protected function applyConditions(\DibiFluent $fluent, $property, $condition, $value) {
        
        // get camel cased name
        $name = $this->propertyToName($property);
        
        // adjust the dibi behaviour
        $self = $this;
        $this->adjustDibiBehaviour($fluent, function() use ($self, $name, &$fluent, $property, $condition, $value) {
            
            // TODO call directly parent::method()
            // PHP 5.3 does not allow accessing protected methods for via $this keyword in an anonymous function
            // we will overcome this by calling the protected method through its reflection
            // for PHP 5.4, this could be replaced by simply callling '$this->method();'
            
            // check if a method exists
            if ($name AND method_exists($self, 'apply' . $name . 'Condition')){
                
                // get the reflection
                $method = new \Nette\Reflection\Method($self, 'apply' . $name . 'Condition');
                // set the method as accessible
                $method->setAccessible(true);

                // call the method
                $fluent = $method->invokeArgs($self, array($fluent, $condition, $value));

            }else{
                
                // get the reflection
                $method = new \Nette\Reflection\Method($self, 'applyCondition');
                // set the method as accessible
                $method->setAccessible(true);

                // call the method
                $fluent = $method->invokeArgs($self, array($fluent, $property, $condition, $value));

            }
            
        });
        
        return $fluent;

    }
    
    /**
     * Applies a simple condition on a DibiFluent.
     * 
     * @param \DibiFluent $fluent
     * @param string|array $column
     * @param string $condition One of the class constants.
     * @param mixed $value
     * @return type
     */
    protected function applyCondition(\DibiFluent $fluent, $column, $condition, $value) {
        
        return $this->{$condition}($fluent, $column, $value);
        
    }

    /**
     * Apply an autoincrement condition.
     * 
     * @param \DibiFluent $fluent
     * @param string $condition One of the class constants.
     * @param mixed $value
     * @return \DibiFluent
     * @throws \Exception
     */
    protected function applyAutoIncrementCondition(\DibiFluent $fluent, $condition, $value) {

        if ($this->autoIncrement === null)
            throw new \Exception('Autoincrement column does not exist.');

        return $this->{$condition}($fluent, $this->autoIncrement, $value);
        
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                             Where statements
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    
    /**
     * Do not add any where condition.
     *
     * @param \DibiFluent $fluent
     * @param string|array $column
     * @param mixed $value
     * @return \DibiFluent
     */
    protected function whereAny($fluent, $column, $value) {

        return $fluent;

    }

    /**
     * Adds an equal where condition to a DibiFluent object.
     *
     * @param \DibiFluent $fluent
     * @param string|array $column
     * @param mixed $value
     * @return \DibiFluent
     */
    protected function whereEqual($fluent, $column, $value) {

        if (is_array($value))
            $fluent->where('%n IN %in', $this->column($column), $value);
        elseif ($value === null)
            $fluent->where('ISNULL(%n)', $this->column($column));
        elseif ($value instanceof \DateTime OR $value instanceof \Nette\DateTime OR $value instanceof \DibiDateTime)
            $fluent->where('%n = %t', $this->column($column), $value);
        else
            $fluent->where('%n = %s', $this->column($column), $value);

        return $fluent;

    }

    /**
     * Adds an unequal where condition to a DibiFluent object.
     *
     * @param \DibiFluent $fluent
     * @param string|array $column
     * @param mixed $value
     * @return \DibiFluent
     */
    protected function whereNotEqual($fluent, $column, $value) {

        if (is_array($value))
            $fluent->where('NOT %n IN %in', $this->column($column), $value);
        elseif ($value === null)
            $fluent->where('NOT ISNULL(%n)', $this->column($column));
        elseif ($value instanceof \DateTime OR $value instanceof \Nette\DateTime OR $value instanceof \DibiDateTime)
            $fluent->where('%n = %t', $this->column($column), $value);
        else
            $fluent->where('NOT %n = %s', $this->column($column), $value);

        return $fluent;

    }

    /**
     * Adds a like where condition to a DibiFluent object.
     *
     * @param \DibiFluent $fluent
     * @param string $column
     * @param mixed $value
     * @return \DibiFluent
     */
    protected function whereLike($fluent, $column, $value) {

        if ($value === null)
            $fluent->where('ISNULL(%n)', $this->column($column));
        else
            $fluent->where('%n LIKE %s', $this->column($column), $value);

        return $fluent;

    }

    /**
     * Adds an unlike where condition to a DibiFluent object.
     *
     * @param \DibiFluent $fluent
     * @param string $column
     * @param mixed $value
     * @return \DibiFluent
     */
    protected function whereNotLike($fluent, $column, $value) {

        if ($value === null)
            $fluent->where('NOT ISNULL(%n)', $this->column($column));
        else
            $fluent->where('NOT %n LIKE %s', $this->column($column), $value);

        return $fluent;

    }

    /**
     * Adds a greater than where condition to a DibiFluent object.
     *
     * @param \DibiFluent $fluent
     * @param string $column
     * @param mixed $value
     * @return \DibiFluent
     */
    protected function whereGreaterThan($fluent, $column, $value) {

        if ($value instanceof \DateTime OR $value instanceof \Nette\DateTime OR $value instanceof \DibiDateTime)
            $fluent->where('%n > %t', $this->column($column), $value);
        else
            $fluent->where('%n > %s', $this->column($column), $value);

        return $fluent;

    }

    /**
     * Adds a greater or equal than where condition to a DibiFluent object.
     *
     * @param \DibiFluent $fluent
     * @param string $column
     * @param mixed $value
     * @return \DibiFluent
     */
    protected function whereGreaterOrEqualThan($fluent, $column, $value) {

        if ($value instanceof \DateTime OR $value instanceof \Nette\DateTime OR $value instanceof \DibiDateTime)
            $fluent->where('%n >= %t', $this->column($column), $value);
        else
            $fluent->where('%n >= %s', $this->column($column), $value);

        return $fluent;

    }

    /**
     * Adds a lower than where condition to a DibiFluent object.
     *
     * @param \DibiFluent $fluent
     * @param string $column
     * @param mixed $value
     * @return \DibiFluent
     */
    protected function whereLowerThan($fluent, $column, $value) {

        if ($value instanceof \DateTime OR $value instanceof \Nette\DateTime OR $value instanceof \DibiDateTime)
            $fluent->where('%n < %t', $this->column($column), $value);
        else
            $fluent->where('%n < %s', $this->column($column), $value);

        return $fluent;

    }

    /**
     * Adds a lower or equal than where condition to a DibiFluent object.
     *
     * @param \DibiFluent $fluent
     * @param string $column
     * @param mixed $value
     * @return \DibiFluent
     */
    protected function whereLowerOrEqualThan($fluent, $column, $value) {

        if ($value instanceof \DateTime OR $value instanceof \Nette\DateTime OR $value instanceof \DibiDateTime)
            $fluent->where('%n <= %t', $this->column($column), $value);
        else
            $fluent->where('%n <= %s', $this->column($column), $value);

        return $fluent;

    }

    /**
     * Adds a between where condition to a DibiFluent object.
     *
     * @param \DibiFluent $fluent
     * @param string $column
     * @param array $value
     * @return \DibiFluent
     */
    protected function whereBetween($fluent, $column, $value) {

        list($from, $to) = $value;
        
        if ($from instanceof \DateTime OR $from instanceof \Nette\DateTime OR $from instanceof \DibiDateTime)
            $fluent->where('%n > %t', $this->column($column), $from);
        else
            $fluent->where('%n > %s', $this->column($column), $from);
        
        if ($to instanceof \DateTime OR $to instanceof \Nette\DateTime OR $to instanceof \DibiDateTime)
            $fluent->where('%n < %t', $this->column($column), $to);
        else
            $fluent->where('%n < %s', $this->column($column), $to);

        return $fluent;

    }

    /**
     * Adds a between or equal where condition to a DibiFluent object.
     *
     * @param \DibiFluent $fluent
     * @param string $column
     * @param array $value
     * @return \DibiFluent
     */
    protected function whereBetweenOrEqual($fluent, $column, $value) {

        list($from, $to) = $value;
        
        if ($from instanceof \DateTime OR $from instanceof \Nette\DateTime OR $from instanceof \DibiDateTime)
            $fluent->where('%n >= %t', $this->column($column), $from);
        else
            $fluent->where('%n >= %s', $this->column($column), $from);
        
        if ($to instanceof \DateTime OR $to instanceof \Nette\DateTime OR $to instanceof \DibiDateTime)
            $fluent->where('%n <= %t', $this->column($column), $to);
        else
            $fluent->where('%n <= %s', $this->column($column), $to);

        return $fluent;

    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                               Filters
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Returns the filter settings for this property.
     *
     * @param string|array $property
     * @return array|boolean Filter settings in an array or false if no filter found.
     */
    protected function getFilterBy($property) {

        foreach($this->filters as $filter){
            if ($filter['property'] === $property)
                return $filter;
        }

        return false;

    }

    /**
     * Removes the permanent filter on this property.
     *
     * @param string|array $property
     * @return boolean
     */
    public function unsetFilterBy($property) {

        foreach($this->filters as $key => $filter){
            if ($filter['property'] === $property){
                unset($this->filters[$key]);
                return true;
            }
        }

        return false;

    }

    /**
     * Removes all permanent filters.
     */
    public function unsetAllFilters() {

        $this->filters = array();

    }

    /**
     * Adds a permanent filter to restrict rows which are being manipulated by this object.
     * The filter will be applied to all future get, modify and remove method calls.
     * Any existing filters applied to the provided column will be overriden.
     *
     * @param string|array $property
     * @param string $condition Condition type. Use one of the class constants.
     * @param mixed $value
     * @throws \InvalidArgumentException
     */
    public function setFilterBy($property, $condition, $value) {

        // check if we have a method to satisfy the given condition
        if (!method_exists($this, $condition))
            throw new \InvalidArgumentException('Invalid condition provided. Please make sure you are using one of the class constants.');

        // override existing filters
        $this->unsetFilterBy($property);

        // save the filter settings
        $this->filters[] = array(
            'property' => $property,
            'condition' => $condition,
            'value' => $value
        );

    }

    /**
     * Turns the filter on for the next query. Provides fluent interface.
     *
     * @param string|array $property
     * @param string $condition Condition type. Use one of the class constants.
     * @param mixed $value
     * @return \Models\ReadOnlyTable
     */
    public function only($property, $condition, $value) {

        // create a cloned object with the filter enabled
        $clone = clone $this;
        $clone->setFilterBy($property, $condition, $value);

        return $clone;

    }

    /**
     * Turns the filter off for the next query. Provides fluent interface.
     *
     * @param string|array $property If not provided, will remove all filters.
     * @return \Models\ReadOnlyTable
     */
    public function any($property = null) {

        // create a cloned object with the filter enabled
        $clone = clone $this;

        if ($property === null)
            $clone->unsetAllFilters();
        else
            $clone->unsetFilterBy($property);

        return $clone;

    }

    /**
     * Applies the current set of filters on the provided DibiFluent object.
     *
     * @param \DibiFluent $fluent
     * @return \DibiFluent
     */
    protected function applyFilters(\DibiFluent $fluent) {

        // apply all current filters on the provided fluent
        foreach($this->filters as $filter){
            $this->applyConditions($fluent, $filter['property'], $filter['condition'], $filter['value']);
        }

        return $fluent;

    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                                 Counting
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Counts all rows.
     *
     * @return int
     */
    public function count() {

        return $this->countBy(NULL, self::IS_ANY, NULL);
        
    }

    /**
     * Counts the number of rows having satysfing the given condition or get an array of row counts grouped by the $column.
     *
     * @param string|array $property Property name or an array with 2 keys - a table name and a column name.
     * @param string $condition Condition type. Use one of the class constants.
     * @param mixed $value Column value.
     * @return int|\DibiResult Row count or the \DibiResult object with row count for each column value.
     */
    public function countBy($property, $condition, $value) {

        // check if we have a method to satisfy the given condition, if any
        if (!method_exists($this, $condition))
            throw new \InvalidArgumentException('Invalid condition provided. Please make sure you are using one of the class constants.');

        // count all rows satysfying the condition
        return count($this->applyConditions($this->select(), $property, $condition, $value));

    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                            Getting single row
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Get one row.
     *
     * @param string $order use of the predefined object constants.
     * @param int $limit
     * @param int $offset
     * @return \DibiRow Found row or false.
     */
    public function one($order = null) {

        return $this->oneBy(NULL, self::IS_ANY, NULL, $order);

    }

    /**
     * Look up the table by a given column value and return a single row.
     *
     * @param string|array $property Property name or an array with 2 keys - a table name and a column name.
     * @param string $condition Condition type. Use one of the class constants.
     * @param mixed $value Column value.
     * @param string $order Order by.
     * @param int $limit Limit.
     * @param int $offset Offset
     * @return \DibiRow Found row or false.
     */
    public function oneBy($property, $condition, $value, $order = null) {

        // check if we have a mtehod to satisfy the given condition
        if (!method_exists($this, $condition))
            throw new \InvalidArgumentException('Invalid condition provided. Please make sure you are using one of the class constants.');

        $select = $this->applyConditions($this->select()->limit(1), $property, $condition, $value);
        
        if ($order !== null)
            $select->orderBy ($order);

        return $this->fetchOne($select);

    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                            Getting multiple rows
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Get all rows.
     *
     * @param string $order use of the predefined object constants.
     * @param int $limit
     * @param int $offset
     * @return DibiRowCollection Found rows.
     */
    public function get($order = null, $limit = 50, $offset = 0) {

        return $this->getBy(NULL, self::IS_ANY, NULL, $order, $limit, $offset);
        
    }

    /**
     * Look up the table by a given column value.
     *
     * @param string|array $property Property name or an array with 2 keys - a table name and a column name.
     * @param string $condition Condition type. Use one of the class constants.
     * @param mixed $value Column value.
     * @param string $order Order by.
     * @param int $limit Limit.
     * @param int $offset Offset
     * @return DibiRowCollection
     */
    public function getBy($property, $condition, $value, $order = null, $limit = null, $offset = null) {

        // check if we have a mtehod to satisfy the given condition
        if (!method_exists($this, $condition))
            throw new \InvalidArgumentException('Invalid condition provided. Please make sure you are using one of the class constants.');

        // apply the provided conditions
        $select = $this->applyConditions($this->select(), $property, $condition, $value);

        if ($order !== null)
            $select->orderBy($order);

        if ($limit !== null)
            $select->limit($limit);

        if ($limit !== null /* cannot specify offset without limit */ AND $offset !== null)
            $select->offset($offset);

        return $this->fetchAll($select);

    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                              Magic
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Creates magic methods for this table.
     *
     * @param string $name
     * @param array $args
     * @return \DibiFluent
     * @throws \Nette\MemberAccessException
     * @throws \InvalidArgumentException
     */
    public function __call($name, $args) {

        // parse the method name and explode parameters
        $components = $this->parseMethodName($name);
        $action = $components[1];
        $propertyName = $components[2];
        $conditionName = empty($components[3]) ? null : $components[3];
        
        // resolve the provided column name and find out the exact database column name
        $property = $this->nameToProperty($propertyName);

        // resolve the provided condition name and find out the associated method name
        $condition = $this->resolveCondition($conditionName);

        // call the correct class method mapped to the resolved action name
        return $this->callMethod($action, $property, $condition, $args);

    }
    
    /**
     * Calls a correct method to execute the requested action. Used by the magic _call method.
     * Allows the object to be called with methods in the following format:
     * setFilterBy[ColumnName][WhereCondition]($columnValue)
     * unsetFilterBy[ColumnName]()
     * only[ColumnName][WhereCondition]($columnValue)
     * any[ColumnName]()
     * countBy[ColumnName][WhereCondition]($columnValue)
     * countDistinctBy[ColumnName]($columnValue)
     * oneBy[ColumnName][WhereCondition]($columnValue, $order)
     * getBy[ColumnName][WhereCondition]($columnValue, $order, $limit = null, $offset = null)
     * The [ColumnName] is a camel cased table column (with words separated with an underscore: '_'). The [WhereCondition] is one of the following:
     * Equal, NotEqual, Not, Like, NotLike, GreaterThan, GreaterOrEqualThan, NotGreaterThan, LowerThan, LowerOrEqualThan, NotLowerThan
     * 
     * @param string $action Action name.
     * @param array $property Property.
     * @param string $condition Condition.
     * @param string $args Other arguments to pass to the method.
     * @return mixed
     * @throws \Nette\MemberAccessException
     */
    protected function callMethod($action, $property, $condition, $args) {
        
        switch ($action) {

            case 'getFilterBy':

                // call a setFilterBy method
                array_unshift($args, $property);
                return call_user_func_array(array($this, 'getFilterBy'), $args);

            case 'setFilterBy':

                // call a setFilterBy method
                array_unshift($args, $property, $condition ?: self::IS_EQUAL);
                return call_user_func_array(array($this, 'setFilterBy'), $args);

            case 'unsetFilterBy':

                // unsetFilterBy does not accept conditions
                if ($condition !== null)
                    throw new \Nette\MemberAccessException("Call to undefined method $name.");

                // call an unsetFilterBy method
                array_unshift($args, $property);
                return call_user_func_array(array($this, 'unsetFilterBy'), $args);

            case 'only':

                // call an only method
                array_unshift($args, $property, $condition ?: self::IS_EQUAL);
                return call_user_func_array(array($this, 'only'), $args);

            case 'any':

                // any does not accept conditions
                if ($condition !== null)
                    throw new \Nette\MemberAccessException("Call to undefined method $name.");

                // call an any method
                array_unshift($args, $property);
                return call_user_func_array(array($this, 'any'), $args);

            case 'countBy':

                // call a countBy method
                array_unshift($args, $property, $condition ?: self::IS_EQUAL);
                return call_user_func_array(array($this, 'countBy'), $args);

            case 'countDistinctBy':

                // call a countDistinct method
                array_unshift($args, $property, $condition ?: self::IS_EQUAL);
                return call_user_func_array(array($this, 'countDistinct'), $args);

            case 'oneBy':

                // call a oneBy method
                array_unshift($args, $property, $condition ?: self::IS_EQUAL);
                return call_user_func_array(array($this, 'oneBy'), $args);

            case 'getBy':

                // call a getBy method
                array_unshift($args, $property, $condition ?: self::IS_EQUAL);
                return call_user_func_array(array($this, 'getBy'), $args);

            default:

                // unknown action name
                throw new \Nette\MemberAccessException("Call to undefined method $action.");

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
        
        // parse the method name
        // '[action]By' methods
        $valid = preg_match('/^([a-zA-Z]+By|only|any)([A-Za-z0-9]+?)(Equal|NotEqual|Not|Like|NotLike|GreaterThan|GreaterOrEqualThan|NotGreaterThan|LowerThan|LowerOrEqualThan|NotLowerThan|Between|BetweenOrEqual)?$/', $name, $matches);
        // unknown method name
        if (!$valid)
            throw new \Nette\MemberAccessException("Call to undefined method $name.");

        return $matches;
        
    }

    /**
     * Converts a camel cased name to a property identifier.
     * 
     * @param string $name
     * @return string|array
     */
    protected function nameToProperty($name) {
        
        return self::camelCaseToUnderscore($name);
        
    }

    /**
     * Converts a property identifier to a camel cased name.
     * 
     * @param string|array $property
     * @return string
     */
    protected function propertyToName($property) {
        
        // glue all array keys together
        if (is_array($property)){
            $property = implode('_', $property);
        }
        
        return self::underscoreToCamelCase($property);
        
    }
    
    /**
     * Translates a camel-cased condition name provided in a magic method call to an associated class method.
     *
     * @param string $name Camel-cased name.
     * @return string Associated method name.
     */
    protected function resolveCondition($condition){

        switch($condition){
            case 'Equal':
                return self::IS_EQUAL;
            case 'NotEqual':
                return self::IS_NOT_EQUAL;
            case 'Not':
                return self::IS_NOT_EQUAL;
            case 'Like':
                return self::IS_LIKE;
            case 'NotLike':
                return self::IS_NOT_LIKE;
            case 'GreaterThan':
                return self::IS_GREATER_THAN;
            case 'GreaterOrEqualThan':
                return self::IS_GREATER_OR_EQUAL_THAN;
            case 'NotGreaterThan':
                return self::IS_LOWER_OR_EQUAL_THAN;
            case 'LowerThan':
                return self::IS_LOWER_THAN;
            case 'LowerOrEqualThan':
                return self::IS_LOWER_OR_EQUAL_THAN;
            case 'NotLowerThan':
                return self::IS_GREATER_OR_EQUAL_THAN;
            case 'Between':
                return self::IS_BETWEEN;
            case 'BetweenOrEqual':
                return self::IS_BETWEEN_OR_EQUAL;
            case '':
                return null;
            default:
                throw new \InvalidArgumentException("Unknown condition '$condition'.");
        }

    }

}