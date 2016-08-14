<?php

namespace PavolEichler\Database;

/**
 * Closure tree slave table service.
 * 
 * @author Pavol Eichler <pavol.eichler@gmail.com>
 */
abstract class ClosureTreeSlaveTable extends Table {

    /**
     * Order by distance.
     */
    const ORDER_DEPTH = "distance ASC";
    /**
     * Order by distance, descending.
     */
    const ORDER_DEPTH_DESC = 'distance DESC';

    /**
     * Distance column name.
     * @var string
     */
    protected $distance = 'distance';

    /**
     * Ancestor column name.
     * @var string
     */
    protected $ancestor = 'ancestor';

    /**
     * Descendant column name.
     * @var string
     */
    protected $descendant = 'descendant';

    /**
     * Autoincrement column name.
     * @var string
     */
    protected $autoIncrement = null;
    
    
    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                             Column names
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Public distance column name getter.
     *
     * @return string
     */
    public function getDistanceColumn() {

        return $this->distance;

    }

    /**
     * Public distance column name setter.
     *
     * @param string $autoincrement
     */
    public function setDistanceColumn($distance) {

        $this->distance = $distance;

    }
    
    /**
     * Public ancestor column name getter.
     *
     * @return string
     */
    public function getAncestorColumn() {

        return $this->ancestor;

    }

    /**
     * Public ancestor column name setter.
     *
     * @param string $autoincrement
     */
    public function setAncestorColumn($ancestor) {

        $this->ancestor = $ancestor;

    }
    
    /**
     * Public descendant column name getter.
     *
     * @return string
     */
    public function getDescendantColumn() {

        return $this->descendant;

    }

    /**
     * Public descendant column name setter.
     *
     * @param string $autoincrement
     */
    public function setDescendantColumn($descendant) {

        $this->descendant = $descendant;

    }
    
    
    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                              Actions
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Adds a new row into the tree.
     * 
     * @param int|\Dibi\Row $parent
     * @param int|\Dibi\Row $child
     */
    public function add($parent, $child){
        
        // get relevant IDs
        $parentId = ($parent instanceof \Dibi\Row) ? $parent->id : $parent;
        $childId = ($child instanceof \Dibi\Row) ? $child->id : $child;
        
        // make it a child of all ancestors and self
        $this->dibi->query('
            INSERT INTO %n
            SELECT
                %n,
                %i AS %s,
                %n + 1 AS %s
            FROM %n
            WHERE
                %n = %i
            UNION ALL
            SELECT 
                %i AS %s,
                %i AS %s,
                0 AS %s
        ', $this->table(self::TABLE_MAIN, self::FORMAT_NAME),
        $this->ancestor, $childId, $this->descendant, $this->distance, $this->distance,
        $this->table(self::TABLE_MAIN, self::FORMAT_NAME), $this->descendant, $parentId,
        $childId, $this->ancestor, $childId, $this->descendant, $this->distance);
        
    }
    
    /**
     * Modifies an existing row and move it to a new position with all its descendants.
     * 
     * @param int|\Dibi\Row $parent
     * @param int|\Dibi\Row $child
     */
    public function modify($parent, $child){
        
        // get relevant IDs
        $parentId = ($parent instanceof \Dibi\Row) ? $parent->id : $parent;
        $childId = ($child instanceof \Dibi\Row) ? $child->id : $child;
        
        // remove all existing connections with former parents, maintain subtree structure
        $this->dibi->query('
            DELETE
                tree
            FROM
                %n AS tree
            JOIN
                %n AS child_has
            ON
                child_has.%n = tree.%n AND child_has.%n = %i
            LEFT JOIN
                %n AS subtree
            ON
                subtree.%n = child_has.%n AND subtree.%n = tree.%n
            WHERE
                subtree.%n IS NULL;
        ',
        $this->table(),
        $this->table(),
        $this->descendant, $this->descendant, $this->ancestor,
        $childId, 
        $this->table(),
        $this->ancestor, $this->ancestor, $this->descendant, $this->ancestor,
        $this->ancestor);
        
        // create all connections with the new parent
        $this->dibi->query('
            INSERT INTO %n
            SELECT
                supertree.%n AS %s,
                subtree.%n AS %s,
                supertree.%n + subtree.%n + 1 AS %s
            FROM
                %n AS supertree
            JOIN
                %n AS subtree
            WHERE
                subtree.%n = %i
            AND
                supertree.%n = %i;
        ',
        $this->table(),
        $this->ancestor, $this->ancestor,
        $this->descendant, $this->descendant,
        $this->distance, $this->distance, $this->distance,
        $this->table(),
        $this->table(),
        $this->ancestor, $childId,
        $this->descendant, $childId);
        
    }
    
    /**
     * Removes a row and all its descendants from the tree. 
     * 
     * @param int|\Dibi\Row $project
     * @param int|\Dibi\Row $row
     */
    public function remove($row){
        
        // get relevant IDs
        $rowId = ($row instanceof \Dibi\Row) ? $row->id : $row;
        
        // remove all connections of child and its descendants
        $this->dibi->query('
            DELETE
                tree
            FROM
                %n AS tree
            JOIN
                %n AS child_has
            ON
                tree.%n = child_has.%n
            WHERE
                child_has.%n = %i
        ',
        $this->table(),
        $this->table(),
        $this->descendant, $this->descendant,
        $this->ancestor, $rowId);
        
    }
    
}