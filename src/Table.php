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
     * The method which modifies the incoming data row for filling a attribute,
     * then return 2-dimensional array with such attributes as keys and corresponding method names as values
     * The method will be called with data row as array as the only parameter
     *
     * @return array
     */
    public function attributeToColumnMethodMapArray();

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
     * Array with all the attributes to be filled in this interaction
     *
     * @return array
     */
    public function getFillAttributes();

    /**
     * Complete class name of the model to which this table to interact
     *
     * @return string
     */
    public function getModel();
}