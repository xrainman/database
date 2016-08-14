<?php

namespace PavolEichler\Database;

/**
 * Base class for an object connected to a database.
 *
 * @author Pavol Eichler <pavol.eichler@gmail.com>
 */
abstract class Database extends \Nette\Object {

    /** @var \Dibi\Connection */
    protected $dibi;

    /**
     *
     * @param \Dibi\Connection $dibi
     * @param \Nette\Caching\Cache $cache
     */
    public function __construct(\Dibi\Connection $dibi){

        $this->dibi = $dibi;

    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                               Tools
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Filters out all array keys not stated as values in the $columns array from the $values array.
     * Example:
     * $values = array('a' => 1, 'b' => 2, 'c' => 3);
     * $columns = array('a', 'c' => 'x');
     * $this->filterValues($values, $columns); // returns array('a' => 1, 'x' => 3)
     *
     * @param array $values The original array to apply the filter on.
     * @param array $columns Filter mask, an array of allowed keys for the $values array or key => value pairs, where key names will be replaced by their respective values in the $values array.
     * @return array Filtered array.
     */
    protected function filterValues($values, $columns = false) {

        // no rules provided, leave the values array as it is
        if (!$columns)
            return $values;

        // parse the rules
        $allow = array();
        $translate = array();
        array_walk($columns, function($value, $key) use (&$allow, &$translate){
            // allow the key equal to this value
            $allow[] = $value;
            // translate this key name to value
            if (!is_int($key))
                $translate[$key] = $value;
        });

        // translate keys
        array_walk($translate, function($value, $key) use (&$values){
            $values[$value] = $values[$key];
        });

        // remove all unallowed keys
        $values = array_intersect_key($values, array_flip($allow));

        return $values;

    }

    /**
     * Check whether the provided array is associative or not.
     *
     * @param array $array
     * @return boolean
     */
    protected static function isAssociativeArray($array) {

        return (bool) count( array_filter( array_keys((array) $array), 'is_string' ));

    }

    /**
     * Convert underscored_names to CamelCased names.
     *
     * @param string $string
     * @return string
     */
    protected static function underscoreToCamelCase($string) {
        
        // validate the provided names
        $invalid = preg_match('/[^a-z0-9_]/', $string);
        if ($invalid)
            throw new \InvalidArgumentException("Only lowercase alphanumeric characters and an underscore are allowed in the underscore cased name.");
        
        // split the string by words, divided by underscores
        preg_match_all('/([a-z0-9]+)_?/', $string, $matches);
        $words = $matches[1];

        // uppercase the words
        array_walk($words, function(&$word){
            $word = ucfirst($word);
        });

        // join words
        $camelCased = implode($words);

        return $camelCased;

    }

    /**
     * Convert CamelCased names to underscored_names.
     *
     * @param string $string
     * @return string
     */
    protected static function camelCaseToUnderscore($string) {
        
        // validate the provided name
        $invalid = preg_match('/[^A-Za-z0-9]/', $string);
        if ($invalid)
            throw new \InvalidArgumentException("Only alphanumeric characters are allowed in the camel cased name.");

        // split the string by words, each one starting with an upper case letter
        preg_match_all('/([A-Z][a-z]*)|([0-9]+)/', $string, $matches);
        $words = $matches[0];

        // lowercase the words
        array_walk($words, function(&$word){
            $word = lcfirst($word);
        });

        // join words with underscores
        $underscoreCased = implode('_', $words);

        return $underscoreCased;

    }
    
    /**
     * Correct the dibi behaivour for the provided statement.
     * 
     * @param \Dibi\Fluent $fluent
     * @param \Models\callable $statement
     */
    protected function adjustDibiBehaviour(\Dibi\Fluent $fluent = null, $statement) {
        
        $command = $fluent === null ? null : $fluent->getCommand();
        
        switch ($command){
            
            case 'UPDATE':
                $defaultClauseSwitches = \Dibi\Fluent::$clauseSwitches;
                \Dibi\Fluent::$clauseSwitches['JOIN'] = 'UPDATE';
                \Dibi\Fluent::$clauseSwitches['INNER JOIN'] = 'UPDATE';
                \Dibi\Fluent::$clauseSwitches['LEFT JOIN'] = 'UPDATE';
                \Dibi\Fluent::$clauseSwitches['RIGHT JOIN'] = 'UPDATE';
                \Dibi\Fluent::$clauseSwitches['LEFT OUTER JOIN'] = 'UPDATE';
                \Dibi\Fluent::$clauseSwitches['RIGHT OUTER JOIN'] = 'UPDATE';

                $defaultModifiers = \Dibi\Fluent::$modifiers;
                \Dibi\Fluent::$modifiers['JOIN'] = '%n';
                \Dibi\Fluent::$modifiers['INNER JOIN'] = '%n';
                \Dibi\Fluent::$modifiers['LEFT JOIN'] = '%n';
                \Dibi\Fluent::$modifiers['RIGHT JOIN'] = '%n';
                \Dibi\Fluent::$modifiers['LEFT OUTER JOIN'] = '%n';
                \Dibi\Fluent::$modifiers['RIGHT OUTER JOIN'] = '%n';

                $result = $statement();

                \Dibi\Fluent::$clauseSwitches = $defaultClauseSwitches;
                \Dibi\Fluent::$modifiers = $defaultModifiers;
                break;
            
            default:
                $defaultModifiers = \Dibi\Fluent::$modifiers;
                \Dibi\Fluent::$modifiers['JOIN'] = '%n';
                \Dibi\Fluent::$modifiers['INNER JOIN'] = '%n';
                \Dibi\Fluent::$modifiers['LEFT JOIN'] = '%n';
                \Dibi\Fluent::$modifiers['RIGHT JOIN'] = '%n';
                \Dibi\Fluent::$modifiers['LEFT OUTER JOIN'] = '%n';
                \Dibi\Fluent::$modifiers['RIGHT OUTER JOIN'] = '%n';
                
                $result = $statement();
                
                \Dibi\Fluent::$modifiers = $defaultModifiers;
                break;
        }
        
        return $result;
        
    }

}