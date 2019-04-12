<?php

namespace Milestone\Interact;

class Controller
{
    public $upload_file_name = 'file';

    public $method_get_model = 'getModel';
    public $method_get_primary_id = 'getPrimaryIdFromImportRecord';

    public $object, $model;

    public function getUploadedFileContent(){
        $file = request()->file($this->upload_file_name);
        $contents = file_get_contents($file);
        return json_decode($contents,true);
    }

    public function initImport($content){
        $table = $content['table'];
        $this->object = $Object = $this->getTableObject($table);
        $this->model = $Model = $this->getModel($Object);
    }

    public function getTableObject($table){
        $class = $this->getTableClass($table);
        return $this->getObject($class);
    }

    private function getTableClass($table){
        return config('interact.namespace') . "\\" . $table;
    }

    private function getObject($class){
        return new $class;
    }

    public function getCallMethod($object,$method,$attrs = []){
        if(method_exists($object,$method))
            return call_user_func_array([$object,$method],$attrs);
        return null;
    }

    private function getModel($object){
        return $this->getObject($this->getCallMethod($object,$this->method_get_model));
    }

    public function getPrimaryId($record){
        return call_user_func_array([$this->object,$this->method_get_primary_id],[$record]);
    }

    public function getFilledAttributes($attributes,$mappings,$record){
        if(!is_array($attributes) || empty($attributes) || !$record || empty($record)) return [];
        $mappings = $this->getCorrectedMappings($mappings);
        $data = array_fill_keys($attributes,null);
        foreach ($data as $key => &$value){
            $value = array_key_exists($key,$mappings)
                ? $this->getFillValue($mappings[$key],$record)
                : (array_key_exists($key,$record) ? $record[$key] : $value);
        }
        return $data;
    }

    private function getCorrectedMappings($mappings){
        if(is_array($mappings)) return $mappings;
        if(is_null($mappings) || empty($mappings)) return [];
        return [$mappings];
    }

    private function getFillValue($map,$record){
        if(method_exists($this->object,$map)) return call_user_func_array([$this->object,$map],[$record]);
        if(array_key_exists($map,$record)) return $record[$map];
        return null;
    }

}
