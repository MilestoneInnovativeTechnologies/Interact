<?php

namespace Milestone\Interact;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class Controller
{
    protected $table;
    protected $tableModel;
    protected $attributes;
    protected $columnMapArray;
    protected $columnMethodMapArray;
    protected $primary_key;

    public function index(Request $request){
        ini_set('max_execution_time',300);
        $Return = [];
        $Contents = $this->getContent(file_get_contents($request->file('file')));
        foreach((array) $Contents as $Content){
            $this->setProperties($Content['table']);
            $this->setSource($Content);
            $this->primary_key = $Content['primary_key'];
            $this->callPreActions($Content);
            $Return[] = $this->callPostActions($Content,$this->run($Content['mode'],$Content['data']));
        }
        return $Return;
    }

    private function getContent($content){
        return json_decode($content,true);
    }

    private function getControllerClass($table){
        return config('interact.namespace') . "\\" . $table;
    }

    private function setProperties($table){
        $class = $this->getControllerClass($table); $table = $this->table = new $class;
        $tableModel = $table->getModel(); $this->tableModel = new $tableModel;
        $this->attributes = $table->getFillAttributes();
        $this->columnMapArray = $table->attributeToColumnMapArray();
        $this->columnMethodMapArray = $table->attributeToColumnMethodMapArray();
    }

    private function setSource($Content){
        foreach($Content as $Key => $Value){
            if(property_exists($this->table,$Key))
                $this->table->$Key = $Value;
        }
    }

    private function run($mode,$data){
        if(!$this->attributes || empty($this->attributes)) return 'Fill attributes fields are empty!';
        $method = "do_" . $mode;
        return call_user_func([$this,$method],$data);
    }

    private function do_create($data){
        $result = [];
        $this->tableModel->unguard();
        foreach ($data as $record) $result[$this->getPrimaryKeyCode($record)] = $this->insertData($record);
        return $result;
    }
    private function do_insert($data){ return $this->do_create($data); }

    private function do_update($data){
        $result = [];
        foreach ($data as $record) $result[$this->getPrimaryKeyCode($record)] = $this->updateData($record);
        return $result;
    }
    private function do_edit($data){ return $this->do_update($data); }

    private function do_read($data){
        if(!$data || empty($data)) return $this->getAllData();
        $result = [];
        foreach ($data as $record) $result[$this->table->getPrimaryValueFromRowData($record)] = $this->getData($record);
        return $result;
    }
    private function do_get($data){ return $this->do_read($data); }

    private function do_delete($data){
        $result = [];
        if($data && !empty($data))
            foreach ($data as $record)
                $result[$this->getPrimaryKeyCode($record)] = $this->deleteRecord($record);
        return $result;
    }
    private function do_destroy($data){ return $this->do_delete($data); }
    private function do_remove($data){ return $this->do_delete($data); }

    private function getPrimaryKeyCode($data){
        if(!$this->primary_key || empty($this->primary_key)) return microtime(true)*1000000000;
        return is_array($this->primary_key) ? implode(config('interact.delimiter'),Arr::only($data,$this->primary_key)) : Arr::get($data,$this->primary_key);
    }

    private function insertData($record){
        return $this->do_skip($record) ?:
            $this->do_done($record,$this->tableModel->create($this->getFillable($record))->getKey());
    }

    private function updateData($record){
        $id = $this->table->getPrimaryValueFromRowData($record);
        if(!$id) return null;
        $skip = $this->do_skip($record); if($skip !== false) return $skip;
        $this->tableModel->find($id)->forceFill($this->getFillable($record))->save();
        return $this->do_done($record,$id);
    }

    private function getAllData(){
        return $this->tableModel->all();
    }

    private function getData($record){
        $id = $this->table->getPrimaryValueFromRowData($record);
        if(!$id) return null;
        $skip = $this->do_skip($record); if($skip !== false) return $skip;
        return $this->tableModel->find($id);
    }

    private function deleteRecord($record){
        $id = $this->table->getPrimaryValueFromRowData($record);
        if(!$id) return null;
        $skip = $this->do_skip($record); if($skip !== false) return $skip;
        return $this->tableModel->destroy($id) ? $this->do_done($record,$id) : null;
    }

    private function getFillable($record){
        $columnMapArray = $this->columnMapArray; $methods = $this->columnMethodMapArray;
        $fillable = [];
        foreach ($this->attributes as $fill){
            if(is_array($methods) && !empty($methods) && array_key_exists($fill,$methods)) $fillable[$fill] = call_user_func([$this->table,$methods[$fill]],$record);
            elseif(is_array($columnMapArray) && !empty($columnMapArray) && array_key_exists($fill,$columnMapArray)) $fillable[$fill] = $record[$columnMapArray[$fill]];
            elseif(is_array($record) && array_key_exists($fill,$record)) $fillable[$fill] = $record[$fill];
        }
        return $fillable;
    }

    private function callPreActions($Content){
        if(method_exists($this->table,'preActions'))
            call_user_func_array([$this->table,'preActions'],[$Content]);
    }

    private function do_skip($record){
        if(method_exists($this->table,'isRecordValid')){
            $valid = $this->table->isRecordValid($record);
            if($valid === true) return false;
            elseif ($valid === false) return 'Record Skipped';
            else return $valid;
        } return false;
    }

    private function do_done($record,$id){
        if(method_exists($this->table,'isDone'))
            $this->table->isDone($record,$id);
        return $id;
    }

    private function callPostActions($Content,$Result){
        $post_action = null;
        if(method_exists($this->table,'postActions'))
            $post_action = call_user_func_array([$this->table,'postActions'],[$Content,$Result]);
        return $post_action ?: $Result;
    }

}
