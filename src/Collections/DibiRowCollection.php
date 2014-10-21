<?php

namespace Zaraguza\Database;

/**
 * Allows advanced manipulation with the results array.
 *
 * @author Pavol Eichler
 */
class DibiRowCollection extends \Nette\ArrayHash {

    /**
     * Creates a new DibiRowCollection from an array.
     *
     * @param \DibiRow[] $arr
     * @param bool $recursive
     * @return \static
     * @throws \Nette\InvalidArgumentException
     */
    public static function from($arr, $recursive = TRUE) {

        // return an empty array for values evaluating to false
        if ($arr == false)
            return new static;
        // other than that, allow arrays only
        elseif (!is_array($arr) AND !$arr instanceof Traversable)
            throw new \Nette\InvalidArgumentException('The provided value is not an array.');

        return parent::from($arr, $recursive);
    }

    /**
     * Check if there are any items in the collection.
     *
     * @return boolean
     */
    public function isEmpty() {

        return !count($this);

    }

    /**
     * Creates a list of all values of a given column.
     *
     * @param string $column
     * @return array
     * @throws \Nette\InvalidArgumentException
     */
    public function asList($column) {

        // no rows
        if (!count($this))
            return array();

        // validate column
        if (!property_exists($this[0], $column))
            throw new \Nette\InvalidArgumentException("Unknown column '$column'.");

        $list = array();

        foreach($this as $row){
            $list[] = $row->{$column};
        }

        return $list;

    }

    /**
     * Creates an array with key => value pairs for the given columns.
     * If multiple values share the same key value, they will be overwritten by the last one.
     *
     * @param string $key Column to use as key.
     * @param string $value Column to use as value.
     * @return array
     * @throws \Nette\InvalidArgumentException
     */
    public function asPairs($key, $value) {

        // no rows
        if (!count($this))
            return array();

        // validate columns
        if (!property_exists($this[0], $key))
            throw new \Nette\InvalidArgumentException("Unknown column '$key'.");
        if (!property_exists($this[0], $value))
            throw new \Nette\InvalidArgumentException("Unknown column '$value'.");

        $pairs = array();

        foreach($this as $row){
            $pairs[$row->{$key}] = $row->{$value};
        }

        return $pairs;

    }

    /**
     * Creates an associative tree. Accepts a list of columns to build the tree by, as arguments.
     * If multiple values share the same key values, they will be overwritten by the last one.
     *
     * @return array
     * @throws \Nette\InvalidArgumentException
     */
    public function asAssoc() {

        // no rows
        if (!count($this))
            return array();

        // get method arguments
        $columns = func_get_args();

        // validate columns
        foreach($columns as $column){
            if (!property_exists($this[0], $column))
                throw new \Nette\InvalidArgumentException("Unknown column '$column'.");
        }

        // build a tree
        $data = array();
        $cursor = &$data;

        // loop through rows
        foreach($this as $row){
            // loop through the required columns to build the associative tree
            foreach($columns as $index => $column){

                if ((count($columns) - 1) == $index){
                    // last column of the tree
                    // assign the row as the final value
                    $cursor[(string) $row->{$column}] = $row;
                    // reset cursor back to the top node
                    $cursor = &$data;
                }else{
                    // any other column
                    // if this value is missing, create it
                    if (!key_exists((string) $row->{$column}, $cursor))
                        $cursor[(string) $row->{$column}] = array();
                    // move the cursor deeper into the structure
                    $cursor = &$cursor[(string) $row->{$column}];
                }

            }
        }

        return $data;

    }
    
    /**
     * Creates a tree.
     * 
     * @param type $parent Parent ID property name.
     * @param type $id Item ID property name.
     */
    public function asTree($parent, $id, &$orphans = array()) {
        
        // no rows
        if (!count($this))
            return array();
 
        // get a associative array with the IDs as keys
        $data = $this->asAssoc($id);
        // build a new tree starting with the root elements
        $tree = array();
        
        // loop through the rows and link their children
        foreach($data as $key => &$row){
            
            // initiate the children property on all items
            if (!isset($row->children)){
                $row->children = array();
            }
            
            // add the root elements to the tree
            if (!$row->{$parent}){
                $tree[] = $row;
                continue;
            }
            
            // corrupt elements missing their parent
            if (!isset($data[$row->{$parent}])){
                $orphans[] = $row;
                continue;
            }
            
            // link the children to their parents
            if (!isset($data[$row->{$parent}]->children)){
                $data[$row->{$parent}]->children = array($row);
            }else{
                $data[$row->{$parent}]->children[] = $row;
            }
            
        }
        
        return $tree;
        
    }

}