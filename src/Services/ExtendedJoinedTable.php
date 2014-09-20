<?php

namespace Zaraguza\Database;

/**
 * Base class for an object providing access to data stored in several SQL tables.
 * Several fetched rows may describe one item.
 *
 * @author Pavol Eichler <pavol.eichler@gmail.com>
 */
abstract class ExtendedJoinedTable extends JoinedTable
{


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                             Model behaviour
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Extends the given fluent select with additional data from joined tables.
     * You can create a complex join, which will retrieve several rows for each object.
     * Then you would parse the results in the fetch() method, e.g. using the dibi::fetchAssoc().
     * As extend is alwyas called only after the limit, offset and order parameters have been applied, it will not interfere with paging.
     *
     * @param \DibiFluent $fluent
     * @return \DibiFluent
     */
    protected function extend(\DibiFluent $fluent) {

        return $this->dibi->select('*')->from($fluent, $this->table(self::TABLE_MAIN, self::FORMAT_ALIAS));

    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                             Selects
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Returns the extended basic select.
     * Call without arguments to retrieve the default fields, or pass custom fields in the same way, you would call the dibi::select() method.
     *
     * @return \DibiFluent
     */
    protected function extended() {

        $args = func_get_arg();
        $select = call_user_func_array(array($this, 'select'), $args);

        $fluent = $this->adjustDibiBehaviour(null, function() use ($self, $select) {
            
            // TODO call directly parent::method()
            // PHP 5.3 does not allow accessing protected methods for via $this keyword in an anonymous function
            // we will overcome this by calling the protected method through its reflection
            // for PHP 5.4, this could be replaced by simply callling '$this->method();'
            
            // get the reflection
            $method = new \Nette\Reflection\Method($self, 'extend');
            // set the method as accessible
            $method->setAccessible(true);

            // call the method
            return $method->invoke($self, $select);
            
        });
        
        return $fluent;

    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     *                                 Tools
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Fetches all rows.
     *
     * @param \DibiFluent $fluent
     * @return \DibiResult The returned rows.
     */
    protected function fetchAll(\DibiFluent $fluent) {

        $fluent = $this->extend($fluent);

        return parent::fetchAll($fluent);
    }

}