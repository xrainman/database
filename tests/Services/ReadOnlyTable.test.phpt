<?php

require '../../vendor/autoload.php';
require '../../src/Services/Database.php';
require '../../src/Services/Table.php';
require '../../src/Services/ReadOnlyTable.php';
require '../../src/Collections/DibiRowCollection.php';

use Tester\Assert;
Tester\Environment::setup();

class Employees extends PavolEichler\Database\ReadOnlyTable {
    const ORDER_EMP_NO = 'emp_no ASC';
    const ORDER_EMP_NO_DESC = 'emp_no DESC';
    protected $autoIncrement = 'emp_no';
}

$connection = new DibiConnection(array(
    'host' => 'localhost',
    'username' => '',
    'password' => '',
    'database' => 'test'
));
$employees = new Employees($connection);

// one
Assert::type('\DibiRow', $employees->one());
Assert::equal(10001, $employees->one(Employees::ORDER_EMP_NO)->emp_no);

// oneBy
Assert::false($employees->oneByEmpNo(10000));
Assert::equal(10003, $employees->oneByEmpNo(10003)->emp_no);
Assert::equal('Bamford', $employees->oneByEmpNo(10003)->last_name);
Assert::equal('Anneke', $employees->oneByFirstName('Anneke')->first_name);
Assert::equal(10006, $employees->oneByFirstName('Anneke', Employees::ORDER_EMP_NO)->emp_no);

// oneByCondition
Assert::equal(10003, $employees->oneByEmpNoEqual(10003, Employees::ORDER_EMP_NO)->emp_no);
Assert::equal(10004, $employees->oneByEmpNoGreaterThan(10003, Employees::ORDER_EMP_NO)->emp_no);
Assert::equal(10002, $employees->oneByEmpNoLowerThan(10003, Employees::ORDER_EMP_NO_DESC)->emp_no);
Assert::equal(10003, $employees->oneByEmpNoLowerOrEqualThan(10003, Employees::ORDER_EMP_NO_DESC)->emp_no);
Assert::equal(10003, $employees->oneByEmpNoGreaterOrEqualThan(10003, Employees::ORDER_EMP_NO)->emp_no);
Assert::equal('Kazuhito', $employees->oneByLastNameLike('Cappell%', Employees::ORDER_EMP_NO)->first_name);
Assert::equal(10002, $employees->oneByLastNameNotLike('Fac%', Employees::ORDER_EMP_NO)->emp_no);

// get
Assert::type('\PavolEichler\Database\DibiRowCollection', $employees->get(null, 10));
Assert::equal(10, count($employees->get(null, 10)));
Assert::equal(array(10001, 10002), $employees->get(Employees::ORDER_EMP_NO, 2)->asList('emp_no')); // limit
Assert::equal(array(10003, 10004), $employees->get(Employees::ORDER_EMP_NO, 2, 2)->asList('emp_no')); // offset

// getBy
Assert::true($employees->getByEmpNo(10000)->isEmpty());
Assert::equal(array(10015, 10017), $employees->getByLastName('Nooteboom', Employees::ORDER_EMP_NO, 2)->asList('emp_no')); // limit
Assert::equal(array(10018, 10020), $employees->getByLastName('Nooteboom', Employees::ORDER_EMP_NO, 2, 2)->asList('emp_no')); // offset

// getByCondition
Assert::equal(array(10002), $employees->getByEmpNoEqual(10002, Employees::ORDER_EMP_NO, 2)->asList('emp_no'));
Assert::equal(array(10001, 10003), $employees->getByEmpNoNotEqual(10002, Employees::ORDER_EMP_NO, 2)->asList('emp_no'));
Assert::equal(array(10004, 10005), $employees->getByEmpNoGreaterThan(10003, Employees::ORDER_EMP_NO, 2)->asList('emp_no'));
Assert::equal(array(10004, 10003), $employees->getByEmpNoLowerThan(10005, Employees::ORDER_EMP_NO_DESC, 2)->asList('emp_no'));
Assert::equal(array(10005, 10004), $employees->getByEmpNoLowerOrEqualThan(10005, Employees::ORDER_EMP_NO_DESC, 2)->asList('emp_no'));
Assert::equal(array(10003, 10004), $employees->getByEmpNoGreaterOrEqualThan(10003, Employees::ORDER_EMP_NO, 2)->asList('emp_no'));
Assert::equal(array(10006, 10009), $employees->getByLastNameLike('P%', Employees::ORDER_EMP_NO, 2)->asList('emp_no'));
Assert::equal(array(10002, 10003), $employees->getByLastNameNotLike('Fac%', Employees::ORDER_EMP_NO, 2)->asList('emp_no'));

// applyAutoIncrementCondition
Assert::equal('Bamford', $employees->oneByAutoIncrement(10003)->last_name);
Assert::equal(array(10004, 10003), $employees->getByAutoIncrementLowerThan(10005, Employees::ORDER_EMP_NO_DESC, 2)->asList('emp_no'));

// filterBy
Assert::false($employees->getFilterByLastName());
$employees->setFilterByLastNameLike('Noo%'); // create a filter
Assert::equal(array('property' => 'last_name', 'condition' => Employees::IS_LIKE, 'value' => 'Noo%'), $employees->getFilterByLastName()); // get filter
$employees->setFilterByFirstNameLike('G%'); // multiple filters
$employees->setFilterByEmpNoGreaterThan(10017);
Assert::equal(array(10018, 10020), $employees->get(Employees::ORDER_EMP_NO, 2)->asList('emp_no'));
Assert::equal(10018, $employees->one(Employees::ORDER_EMP_NO)->emp_no);
$employees->unsetFilterByEmpNo(); // unset a filter
Assert::equal(array(10015, 10017), $employees->get(Employees::ORDER_EMP_NO, 2)->asList('emp_no'));
Assert::equal(10015, $employees->one(Employees::ORDER_EMP_NO)->emp_no);
$employees->setFilterByFirstNameLike('A%'); // overwrite the former filter
Assert::equal(array(10022), $employees->get(Employees::ORDER_EMP_NO)->asList('emp_no'));
Assert::equal(10022, $employees->one(Employees::ORDER_EMP_NO)->emp_no);
$employees->unsetAllFilters(); // unset all filters
Assert::equal(array(10001, 10002), $employees->get(Employees::ORDER_EMP_NO, 2)->asList('emp_no'));
Assert::equal(10001, $employees->one(Employees::ORDER_EMP_NO)->emp_no);

// only, any
$employees2 = $employees->onlyLastNameLike('Puf%');
Assert::equal(array('property' => 'last_name', 'condition' => Employees::IS_LIKE, 'value' => 'Puf%'), $employees2->getFilterByLastName());
$employees3 = $employees->onlyLastNameLike('Puf%')->onlyFirstNameLike('C%');
Assert::equal(array('property' => 'last_name', 'condition' => Employees::IS_LIKE, 'value' => 'Puf%'), $employees3->getFilterByLastName());
Assert::equal(array('property' => 'first_name', 'condition' => Employees::IS_LIKE, 'value' => 'C%'), $employees3->getFilterByFirstName());
$employees4 = $employees->onlyLastNameLike('Puf%')->onlyFirstNameLike('C%')->onlyFirstNameLike('A%');
Assert::equal(array('property' => 'first_name', 'condition' => Employees::IS_LIKE, 'value' => 'A%'), $employees4->getFilterByFirstName());
$employees5 = $employees->onlyLastNameLike('Puf%')->onlyFirstNameLike('C%')->onlyEmpNoGreaterThan(30000)->anyEmpNo();
Assert::false($employees5->getFilterByEmpNo());
$employees6 = $employees->onlyLastNameLike('Puf%')->onlyFirstNameLike('C%')->onlyEmpNoGreaterThan(30000)->any();
Assert::false($employees6->getFilterByFirstName());
Assert::false($employees6->getFilterByLastName());

// count
Assert::equal(22, $employees->count());
Assert::equal(5, $employees->onlyLastNameLike('Noote%')->onlyFirstNameLike('G%')->count());
Assert::equal(0, $employees->countByEmpNo(10000));
Assert::equal(1, $employees->countByEmpNo(10001));
Assert::equal(2, $employees->countByFirstName('Georgi'));