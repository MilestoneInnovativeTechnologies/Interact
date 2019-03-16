<?php
namespace Milestone\Interact;

interface Table
{
    /**
     * This function must return the primary id of the data row from the table is interacting
     * This function may either look into any model to get primary id value or generate from the data row
     * Mostly this function used in read, update and delete mode
     *
     * @param $data
     * @return string | null
     */
    public function getPrimaryValueFromRowData($data);

    /**
     * Complete class name of the model to which this table to interact
     *
     * @return string
     */
    public function getModel();

    /**
     * Array with all the attributes to be filled in this interaction
     *
     * @return array
     */
    public function getFillAttributes();

    /**
     * If the incoming data row have the key which is using same as the primary key of the current interaction
     * table, then return that otherwise null.
     * If null, then there should be a method name which returns the primary value from data row
     *
     * @return string | null
     */
    public function getColumnForPrimaryKey();

    /**
     * The method name, which is used to get primary value from data row.
     * If any incoming data key is using as primary value, then define that in getColumnForPrimaryKey.
     * The method will be called with data row as array as the only parameter
     *
     * @return string | null
     */
    public function methodNameToGetPrimaryValue();

    /**
     * If the incoming data can be mapped directly to the any attribute in fill attributes
     * then that attributes are passed as the key with corresponding column as values.
     * If any modification required in between mapping, then the method name, which performs
     * the action is given as the value
     *
     * @return array
     */
    public function attributeToColumnMapArray();

    /**
     * The method which modifies the incoming data row for filling a attribute,
     * then return 2-dimensional array with such attributes as keys and corresponding method names as values
     * The method will be called with data row as array as the only parameter
     *
     * @return array
     */
    public function attributeToColumnMethodMapArray();

}