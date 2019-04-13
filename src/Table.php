<?php
namespace Milestone\Interact;

interface Table
{
    /**
     * A 2-D array which maps each element in the import attributes to
     * the method in same class file which return the value from each record
     * or the key in record which is to be filled for that particular attribute
     * If no key found for an attribute in map, the attribute will be searched
     * in record array, if found corresponding value will be returned
     * or return null
     *
     *
     * @return array
     */
    public function getImportMappings();

    /**
     * Array with all the attributes to be filled in this interaction
     *
     * @return array
     */
    public function getImportAttributes();
    /**
     * A 2-D array which maps each element in the export attributes to
     * the method in same class file which return the value from each eloquent record
     * or the key in eloquent record which is to be the value for that particular attribute
     * If no key found for an attribute in map, the attribute will be searched
     * in record array, if found corresponding value will be returned
     * or return null
     *
     *
     * @return array
     */
    public function getExportMappings();

    /**
     * Array with all the attributes to be filled in this interaction
     *
     * @return array
     */
    public function getExportAttributes();
    /**
     * This function must return the primary id of the data row from the table is interacting
     * This function may either look into any model to get primary id value or generate from the data row
     * Mostly this function used in read, update and delete mode
     *
     * @param $data
     * @return string | null
     */
    public function getPrimaryIdFromImportRecord($data);

    /**
     * Complete class name of the model to which this table to interact
     *
     * @return string
     */
    public function getModel();
}