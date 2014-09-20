<?php

require '../../vendor/autoload.php';
require '../../src/Services/Database.php';

use Tester\Assert;
Tester\Environment::setup();

class DibiConnection {}

class Mock extends \Zaraguza\Database\Database {
    public function _filterValues($values, $columns = false) { return $this->filterValues($values, $columns); }
    public static function _isAssociativeArray($array) { return self::isAssociativeArray($array); }
    public static function _underscoreToCamelCase($string) { return self::underscoreToCamelCase($string); }
    public static function _camelCaseToUnderscore($string) { return self::camelCaseToUnderscore($string); }
}

$connection = new DibiConnection();
$database = new Mock($connection);


Assert::true($database::_isAssociativeArray(array('a' => 1)));
Assert::true($database::_isAssociativeArray(array('a' => 1, 'b' => 2)));
Assert::false($database::_isAssociativeArray(array(1 => 1)));
Assert::false($database::_isAssociativeArray(array(1,2,3, 5 => 'puf')));
Assert::false($database::_isAssociativeArray(array(1,2,3)));
Assert::false($database::_isAssociativeArray(array('pam')));
Assert::false($database::_isAssociativeArray(array()));
Assert::error($database::_isAssociativeArray(null), E_WARNING);
Assert::error($database::_isAssociativeArray('pam'), E_WARNING);

Assert::equal('One', $database::_underscoreToCamelCase('one'));
Assert::equal('One', $database::_underscoreToCamelCase('_one'));
Assert::equal('OneTwo', $database::_underscoreToCamelCase('one_two'));
Assert::equal('OneTwoThree', $database::_underscoreToCamelCase('one_two_three'));
Assert::equal('OneTwo23', $database::_underscoreToCamelCase('one_two2_3'));
Assert::exception(function() use ($database){
    $database::_underscoreToCamelCase('one-two');
}, 'InvalidArgumentException');
Assert::exception(function() use ($database){
    $database::_underscoreToCamelCase('one two');
}, 'InvalidArgumentException');
Assert::exception(function() use ($database){
    $database::_underscoreToCamelCase('OneTwo');
}, 'InvalidArgumentException');

Assert::equal('one', $database::_camelCaseToUnderscore('One'));
Assert::equal('one_two', $database::_camelCaseToUnderscore('OneTwo'));
Assert::equal('one_two_three', $database::_camelCaseToUnderscore('OneTwoThree'));
Assert::equal('one_two23', $database::_camelCaseToUnderscore('OneTwo23'));
Assert::exception(function() use ($database){
    $database::_camelCaseToUnderscore('One-Two');
}, 'InvalidArgumentException');
Assert::exception(function() use ($database){
    $database::_camelCaseToUnderscore('One Two');
}, 'InvalidArgumentException');
Assert::exception(function() use ($database){
    $database::_camelCaseToUnderscore('one_two');
}, 'InvalidArgumentException');

$values = array(
    'a' => 1,
    'b' => 2,
    'c' => 3,
    'd' => 4,
    'e' => 5,
    'f' => 6 
);
Assert::equal($values, $database->_filterValues($values));
Assert::equal($values, $database->_filterValues($values, false));
Assert::equal(array('c' => 3), $database->_filterValues($values, array('c')));
Assert::equal(array('a' => 1, 'c' => 3), $database->_filterValues($values, array('a', 'c')));
Assert::equal(array('a' => 3), $database->_filterValues($values, array('c' => 'a')));
Assert::equal(array('b' => 1, 'd' => 3), $database->_filterValues($values, array('a' => 'b', 'c' => 'd')));
Assert::equal(array('a' => 1, 'b' => 2, 'c' => 6), $database->_filterValues($values, array('a', 'b', 'f' => 'c')));