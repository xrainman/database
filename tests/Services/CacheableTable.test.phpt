<?php

require '../../vendor/autoload.php';
require '../../src/Services/Database.php';
require '../../src/Services/Table.php';
require '../../src/Services/ReadOnlyTable.php';
require '../../src/Services/CacheableTable.php';
require '../../src/Collections/DibiRowCollection.php';

use Tester\Assert;
Tester\Environment::setup();

Assert::true(true);