<?php

namespace Milestone\Interact;

use Carbon\Carbon;
use Illuminate\Http\Request;

class Export extends Controller
{

    public $method_get_attributes = 'getExportAttributes';
    public $method_get_mappings = 'getExportMappings';
    public $method_is_valid_get = 'isValidExportGet';
    public $method_is_valid_update = 'isValidExportUpdate';
    public $method_get_exported = 'recordGetExported';
    public $method_update_exported = 'recordUpdateExported';

    protected $attributes, $mappings;

    private $pre_export_get = 'preExportGet';
    private $post_export_get = 'postExportGet';
    private $pre_export_update = 'preExportUpdate';
    private $post_export_update = 'postExportUpdate';

    private $table, $created_at, $updated_at;

    public $data = [];
    public $query_get,$query_update;
    public $data_get,$data_update;

    public function index($table_name, Request $request){
        $this->initExport($table_name); if(!$this->model) return null;
        $data = $this->getExportData($table_name,$request->created_at, $request->updated_at);
        if(!empty($data)) $this->updateTimings($table_name);
        return Out::data($data);
    }

    public function getExportData($table, $created_at, $updated_at){
        $this->setExport($table, $created_at, $updated_at);
        if(is_null($this->model)) return [];
        $this->setQuery($created_at, $updated_at);
        $this->setData(); $this->prepareData();
        if(empty($this->data)) return [];
        return $this->data;
    }

    private function setExport($table, $created_at, $updated_at){
        $array = compact('table','created_at','updated_at');
        $this->attributes = $this->getAttributes($this->object);
        $this->mappings = $this->getMappings($this->object);
        foreach($array as $key => $value){
            $this->$key = $value;
            if(property_exists($this->object,$key))
                $this->object->$key = $value;
        }
    }

    private function getAttributes($object){
        return $this->getCallMethod($object,$this->method_get_attributes);
    }

    private function getMappings($object){
        return $this->getCallMethod($object,$this->method_get_mappings);
    }

    private function setQuery($created_at,$updated_at){
        if($created_at) $this->setGetQuery($this->earlierTime($created_at));
        if($updated_at) $this->setUpdateQuery($this->earlierTime($created_at),$this->earlierTime($updated_at));
    }

    private function earlierTime($time){
        return $time ?: Carbon::create(1900)->toDateTimeString();
    }

    private function setGetQuery($time){
        $this->query_get = $this->model->query()
            ->where(function($Q) use($time){ $Q->where('created_at','>',$time); })
            ->orderBy('created_at','asc')
        ;
    }

    private function setUpdateQuery($create,$update){
        $this->query_update = $this->model->query()
            ->where(function($Q) use($create,$update){
                $Q->where('updated_at','>',$update)
                    ->where('created_at','<=',$create);
                ;
            })
            ->orderBy('created_at','asc')
        ;
    }

    private function setData(){
        $query_get = $this->getCallMethod($this->object,$this->pre_export_get,[$this->query_get]) ?: $this->query_get;
        $query_update = $this->getCallMethod($this->object,$this->pre_export_update,[$this->query_update]) ?: $this->query_update;
        if($this->query_get) $this->setGetData($query_get);
        if($this->query_update) $this->setUpdateData($query_update);
    }

    private function setGetData($query){
        $this->data_get = $query->get();
    }

    private function setUpdateData($query){
        $this->data_update = $query->get();
    }

    private function prepareData(){
        if($this->data_get){
            $dataGet = $this->getDataGet();
            array_push($this->data,$dataGet);
            $this->getCallMethod($this->object,$this->post_export_get,[$dataGet]);
        }
        if($this->data_update){
            $dataUpdate = $this->getDataUpdate();
            array_push($this->data,$dataUpdate);
            $this->getCallMethod($this->object,$this->post_export_update,[$dataUpdate]);
        }
    }

    private function getDataGet(){
        $headers = $this->Headers('insert');
        $dataGet = [];
        foreach($this->data_get as $get){
            $expData = $this->getValidExportGetData($get);
            if($expData){
                $expData = $this->getCallMethod($this->object,$this->method_get_exported,[$get,$expData]) ?: $expData;
                $dataGet[$get->id] = $expData;
            }
        }
        return array_merge($headers,['data' => $dataGet]);
    }

    private function Headers($mode = 'insert'){
        $table = $this->table;
        $query_updated_at = $this->updated_at;
        $query_created_at = $this->created_at;
        $record_updated_at = $this->data_get->max('updated_at')->toDateTimeString();
        $record_created_at = $this->data_get->max('created_at')->toDateTimeString();
        return ['table' => $table, 'mode' => $mode,
            'query' => ['created_at' => $query_created_at,'updated_at' => $query_updated_at],
            'record' => ['created_at' => $record_created_at,'updated_at' => $record_updated_at]
        ];
    }

    private function getValidExportGetData($data){
        $isValid = $this->getCallMethod($this->object,$this->method_is_valid_get,[$data]);
        return (is_null($isValid) || $isValid === true) ? $this->getFilledAttributes($this->attributes,$this->mappings,$data->toArray()) : false;
    }

    private function getDataUpdate(){
        $headers = $this->Headers('update');
        $dataUpdate = [];
        foreach($this->data_update as $update){
            $expData = $this->getValidExportUpdateData($update);
            if($expData){
                $expData = $this->getCallMethod($this->object,$this->method_update_exported,[$update,$expData]) ?: $expData;
                $dataUpdate[$update->id] = $expData;
            }

        }
        return array_merge($headers,['data' => $dataUpdate]);
    }

    private function getValidExportUpdateData($data){
        $isValid = $this->getCallMethod($this->object,$this->method_is_valid_update,[$data]);
        return (is_null($isValid) || $isValid === true) ? $this->getFilledAttributes($this->attributes,$this->mappings,$data->toArray()) : false;
    }
}