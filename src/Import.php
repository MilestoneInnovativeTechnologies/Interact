<?php

namespace Milestone\Interact;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class Import extends Controller
{

    public $method_get_attributes = 'getImportAttributes';
    public $method_get_mappings = 'getImportMappings';
    public $method_get_is_valid = 'isValidImportRecord';
    public $method_imported = 'recordImported';

    private $pre_import = 'preImport';
    private $post_import = 'postImport';

    protected $attributes, $mappings;
    protected $primary_key;

    public function index(Request $request){
        $exec_time_limit = ini_get('max_execution_time');
        $Return = [];
        $Contents = $this->getUploadedFileContent(); if(empty($Contents)) return $Return;
        foreach((array) $Contents as $Content){
            $this->initImport($Content); $this->setImport($Content); $this->primary_key = $Content['primary_key'];
            $Content = $this->callPreImport($Content) ?: $Content;
            set_time_limit($exec_time_limit += count($Content['data']));
            $Return[] = $this->callPostImport($Content,$this->run($Content['mode'],$Content['data']));
        }
        return $Return;
    }

    private function setImport($Content){
        $this->attributes = $this->getAttributes($this->object);
        $this->mappings = $this->getMappings($this->object);
        foreach($Content as $Key => $Value)
            if(property_exists($this->object,$Key))
                $this->object->$Key = $Value;
    }

    private function getAttributes($object){ return $this->getCallMethod($object,$this->method_get_attributes); }
    private function getMappings($object){ return $this->getCallMethod($object,$this->method_get_mappings); }
    private function callPreImport($Content){ return $this->getCallMethod($this->object,$this->pre_import,[$Content]); }
    private function callPostImport($Content,$Result){ return $this->getCallMethod($this->object,$this->post_import,[$Content,$Result]) ?: $Result; }

    private function run($mode,$data){
        if(!$this->attributes || empty($this->attributes)) return 'Fill attributes fields are empty!';
        $method = "do_" . $mode;
        return method_exists($this,$method) ? call_user_func([$this,$method],$data) : null;
    }

    private function do_create($data){
        $result = [];
        $this->model->unguard();
        foreach ($data as $record) $result[$this->getRecordKeyCode($record)] = $this->insertData($record);
        return $result;
    }
    private function do_insert($data){ return $this->do_create($data); }

    private function do_update($data){
        $result = [];
        foreach ($data as $record) $result[$this->getRecordKeyCode($record)] = $this->updateData($record);
        return $result;
    }
    private function do_edit($data){ return $this->do_update($data); }

    private function do_read($data){
        if(!$data || empty($data)) return $this->getAllData();
        $result = [];
        foreach ($data as $record) $result[$this->getPrimaryId($record)] = $this->getData($record);
        return $result;
    }
    private function do_get($data){ return $this->do_read($data); }

    private function do_delete($data){
        $result = [];
        if($data && !empty($data))
            foreach ($data as $record)
                $result[$this->getRecordKeyCode($record)] = $this->deleteRecord($record);
        return $result;
    }
    private function do_destroy($data){ return $this->do_delete($data); }
    private function do_remove($data){ return $this->do_delete($data); }

    private function do_update_or_create($data){
        $result = [];
        foreach ($data as $record){
            $id = $this->getPrimaryId($record);
            $method = ($id) ? 'do_update' : 'do_create';
            $result = array_merge($result,call_user_func([$this,$method],[$record]));
        }
        return $result;
    }

    private function do_delete_and_create($data){
        $this->model->query()->delete();
        return $this->do_create($data);
    }

    private function getRecordKeyCode($data){
        if(!$this->primary_key || empty($this->primary_key)) return microtime(true)*1000000000;
        return is_array($this->primary_key) ? implode(config('interact.delimiter'),Arr::only($data,$this->primary_key)) : Arr::get($data,$this->primary_key);
    }

    private function insertData($record){
        return $this->do_skip($record) ?:
            $this->do_done($record,$this->model->create($this->getFilledAttributes($this->attributes,$this->mappings,$record))->getKey());
    }

    private function updateData($record){
        $id = $this->getPrimaryId($record);
        if(!$id) return null;
        $skip = $this->do_skip($record); if($skip !== false) return $skip;
        $this->model->find($id)->forceFill($this->getFilledAttributes($this->attributes,$this->mappings,$record))->save();
        return $this->do_done($record,$id);
    }

    private function getAllData(){
        return $this->model->all();
    }

    private function getData($record){
        $id = $this->getPrimaryId($record);
        if(!$id) return null;
        $skip = $this->do_skip($record); if($skip !== false) return $skip;
        return $this->model->find($id);
    }

    private function deleteRecord($record){
        $id = $this->getPrimaryId($record);
        if(!$id) return null;
        $skip = $this->do_skip($record); if($skip !== false) return $skip;
        return $this->model->destroy($id) ? $this->do_done($record,$id) : null;
    }

    private function do_skip($record){
        if(method_exists($this->object,$this->method_get_is_valid)){
            $valid = call_user_func([$this->object,$this->method_get_is_valid],$record);
            if($valid === true) return false;
            elseif ($valid === false) return 'Record not valid for import action';
            else return $valid;
        } return false;
    }

    private function do_done($record,$id){
        $this->getCallMethod($this->object,$this->method_imported,[$record,$id]);
        return $id;
    }

}
