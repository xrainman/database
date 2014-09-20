<?php

require '../../vendor/autoload.php';
require '../../src/Services/Database.php';
require '../../src/Services/Table.php';

use Tester\Assert;
Tester\Environment::setup();

class DibiConnection {}

class Mock extends Zaraguza\Database\Table {
    public function _table($key = self::TABLE_MAIN, $alias = self::FORMAT_ALIASED_NAME){ return $this->table($key, $alias); }
    public function _column($column, $type = self::COLUMN_SQL_IDENTIFIER){ return $this->column($column, $type); }
}

$connection = new DibiConnection();
$table1 = new Mock($connection);
$table2 = new Mock($connection, 'mock_2');
$table3 = new Mock($connection, array('mock' => 'mock_3'));

Assert::equal(array('mock' => 'mock'), $table1->getTable());
Assert::equal(array('mock_2' => 'mock_2'), $table2->getTable());
Assert::equal(array('mock' => 'mock_3'), $table3->getTable());

$table2->setTable(array('mock' => 'mock_2'));

Assert::equal(array('mock' => 'mock_2'), $table2->getTable());

Assert::equal('id', $table1->getAutoIncrementColumn());

$table2->setAutoIncrementColumn('id2');
$table3->setAutoIncrementColumn(null);

Assert::equal('id2', $table2->getAutoIncrementColumn());
Assert::null($table3->getAutoIncrementColumn());

Assert::equal(array('mock_3' => 'mock'), $table3->_table());
Assert::equal(array('mock_3' => 'mock'), $table3->_table($table3::TABLE_MAIN));
Assert::equal(array('mock_3' => 'mock'), $table3->_table($table3::TABLE_MAIN, $table3::FORMAT_ALIASED_NAME));
Assert::equal('mock_3', $table3->_table($table3::TABLE_MAIN, $table3::FORMAT_NAME));
Assert::equal('mock', $table3->_table($table3::TABLE_MAIN, $table3::FORMAT_ALIAS));
Assert::equal(array('mock_3' => 'pam'), $table3->_table($table3::TABLE_MAIN, 'pam'));
Assert::equal(array('mock_3' => 'mock'), $table3->_table('mock'));
Assert::equal(array('mock_3' => 'mock'), $table3->_table('mock', $table3::FORMAT_ALIASED_NAME));
Assert::equal('mock_3', $table3->_table('mock', $table3::FORMAT_NAME));
Assert::equal('mock', $table3->_table('mock', $table3::FORMAT_ALIAS));
Assert::equal(array('puf' => 'puf'), $table3->_table('puf'));
Assert::equal(array('puf' => 'puf'), $table3->_table('puf', $table3::FORMAT_ALIASED_NAME));
Assert::equal('puf', $table3->_table('puf', $table3::FORMAT_NAME));
Assert::equal('puf', $table3->_table('puf', $table3::FORMAT_ALIAS));
Assert::equal(array('puf' => 'pam'), $table3->_table('puf', 'pam'));
Assert::exception(function() use ($table3){
    $table3->_table($table3::TABLE_MAIN, 123);
}, 'InvalidArgumentException');
Assert::exception(function() use ($table3){
    $table3->_table('puf', 123);
}, 'InvalidArgumentException');

Assert::equal('mock.abc', $table3->_column('abc'));
Assert::equal('mock.abc', $table3->_column('abc', $table3::COLUMN_SQL_IDENTIFIER));
Assert::equal('mock', $table3->_column('abc', $table3::TABLE_NAME));
Assert::equal('abc', $table3->_column('abc', $table3::COLUMN_NAME));
Assert::equal('pam.puf', $table3->_column(array('pam', 'puf')));
Assert::equal('pam.puf', $table3->_column(array('pam', 'puf'), $table3::COLUMN_SQL_IDENTIFIER));
Assert::equal('pam', $table3->_column(array('pam', 'puf'), $table3::TABLE_NAME));
Assert::equal('puf', $table3->_column(array('pam', 'puf'), $table3::COLUMN_NAME));