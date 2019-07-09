<?php

namespace Milestone\Interact;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class SyncController extends Controller
{
    private $requestTable, $client, $sync, $underlyingTable, $useInterface = true;
    private $exportInteractObjectAttributes, $exportInteractObjectMappings;
    private $importInteractObjectAttributes, $importInteractObjectMappings;
    private $primaryKeys;


    public function index($client,$table){
        $this->setupSync($table,$client);
        $this->useInterface = (request()->has('interface') && request()->interface == 'false') ? false : $this->useInterface;
        $this->setExportInteractObjectProperties(); $Activities = $this->getExportNewRecords();
        $this->startImportNewRecords();
        if($Activities && !empty($Activities)) return Out::data($Activities);
        return null;
    }
    public function delete(Request $request){ if($request->has('client')); SYNC::delete($request->client); }

    public function setupSync($table,$client){
        parent::initSync($table);
        $this->requestTable = $table; $this->client = $client;
        $this->underlyingTable = $this->model->getTable();
        $this->sync = SYNC::client($client,$this->underlyingTable)->get();
    }

    private function setExportInteractObjectProperties(){
        $this->exportInteractObjectAttributes = $this->getCallMethod($this->object,SYNCHelper::$method_get_attributes);
        $this->exportInteractObjectMappings = $this->getCallMethod($this->object,SYNCHelper::$method_get_mappings);
        $table = $this->requestTable;
        $created_at = $this->getTimesFromSync('created')['client']->toDateTimeString();
        $updated_at = $this->getTimesFromSync('updated')['client']->toDateTimeString();
        $array = compact('table','created_at','updated_at');
        foreach($array as $key => $value) if(property_exists($this->object,$key)) $this->object->$key = $value;
    }

    private function getExportNewRecords(){
        $Activities = [];
        if($this->checkIfHaveNewRecord('updated')){
            $activity = $this->getNewlyUpdatedActivity($this->requestTable,request()->created ?: $this->getTimesFromSync('created')['client']->toDateTimeString(),request()->updated ?: $this->getTimesFromSync('updated')['client']->toDateTimeString());
            if($activity) $Activities[] = $activity;
        }
        if($this->checkIfHaveNewRecord('created')){
            $activity = $this->getNewlyCreatedActivity($this->requestTable,request()->created ?: $this->getTimesFromSync('created')['client']->toDateTimeString());
            if($activity) $Activities[] = $activity;
        }
        return $Activities;
    }

    private function checkIfHaveNewRecord($type){
        if(request()->$type) return true;
        if($type === 'updated' && !Arr::get($this->sync,'table.updated')) return false;
        if($type === 'created' && !Arr::get($this->sync,'table.created')) return true;
        $table = Carbon::now(); $client = Carbon::create(1900);
        extract($this->getTimesFromSync($type));
        return $table->greaterThan($client);
    }
    private function getTimesFromSync($type){
        $table_sync_time = Arr::get($this->sync,"table.{$type}",0);
        $client_sync_time = request()->get($type . '_at',null) ?: Arr::get($this->sync,"client.{$type}",0);
        $table = $table_sync_time ? Carbon::createFromTimeString($table_sync_time) : Carbon::now();
        $client = $client_sync_time ? Carbon::createFromTimeString($client_sync_time) : Carbon::create(1900);
        return compact('table','client');
    }

    private function getNewlyCreatedActivity($table,$created_at){
        $data = $this->fetchExportNewlyCreatedRecords($created_at);
        if($data->isEmpty()) return null;
        $activity = SYNCHelper::wrapWithActivityProperties($table,'create',$data->toArray());
        return $activity;
    }
    private function getNewlyUpdatedActivity($table,$created_at,$updated_at){
        $data = $this->fetchExportNewlyUpdatedRecords($created_at,$updated_at);
        if($data->isEmpty()) return null;
        $activity = SYNCHelper::wrapWithActivityProperties($table,'update',$data);
        return $activity;
    }

    private function fetchExportNewlyCreatedRecords($created_at){
        $exportNewRecordsQuery = SYNCHelper::newlyCreatedRecordsFetchQuery($this->model,$created_at);
        if($this->useInterface){
            $exportNewRecordsQuery = $this->getCallMethod($this->object,SYNCHelper::$pre_export_get,[$exportNewRecordsQuery]) ?: $exportNewRecordsQuery;
            $data = $exportNewRecordsQuery->get();
            if($data->isNotEmpty()) $this->updateClientTableRecordDate('created',$data->max($this->model->getCreatedAtColumn())->toDateTimeString());
            return $this->getInterfaceAppliedExportData($data);
        } else {
            $data = $exportNewRecordsQuery->get();
            if($data->isNotEmpty()) $this->updateClientTableRecordDate('created',$data->max($this->model->getCreatedAtColumn())->toDateTimeString());
            return $data;
        }
    }
    private function fetchExportNewlyUpdatedRecords($created_at,$updated_at){
        $exportUpdatedRecordsQuery = SYNCHelper::newlyUpdatedRecordsFetchQuery($this->model,$created_at,$updated_at);
        if($this->useInterface){
            $exportUpdatedRecordsQuery = $this->getCallMethod($this->object,SYNCHelper::$pre_export_update,[$exportUpdatedRecordsQuery]) ?: $exportUpdatedRecordsQuery;
            $data = $exportUpdatedRecordsQuery->get();
            if($data->isNotEmpty()) $this->updateClientTableRecordDate('updated',$data->max($this->model->getUpdatedAtColumn())->toDateTimeString());
            return $this->getInterfaceAppliedExportData($data);
        } else {
            $data = $exportUpdatedRecordsQuery->get();
            if($data->isNotEmpty()) $this->updateClientTableRecordDate('updated',$data->max($this->model->getUpdatedAtColumn())->toDateTimeString());
            return $data;
        }
    }
    private function getInterfaceAppliedExportData($data){
        $result = [];
        if(!empty($data)) foreach ($data as $record){
            if($this->getCallMethod($this->object,SYNCHelper::$method_is_valid_get,[$record]) === false) continue;
            $filledData = $this->getFilledAttributes($this->exportInteractObjectAttributes, $this->exportInteractObjectMappings, $record->toArray());
            $this->getCallMethod($this->object,SYNCHelper::$method_get_exported,[$filledData,$record->id]);
            $result[] = $filledData;
        }
        return collect($result);
    }


    private function updateClientTableRecordDate($type,$date){
        $method = 'set' . ucfirst($type);
        SYNC::client($this->client,$this->underlyingTable)->$method(now()->toDateTimeString(),$date);
        if($type === 'created' && !Arr::get($this->sync,'client.updated'))
            SYNC::client($this->client,$this->underlyingTable)->setUpdated(now()->toDateTimeString(),$date);
    }


    private function startImportNewRecords(){
        $Activities = $this->getUploadedFileContent(); if(!$Activities || empty($Activities)) return;
        $this->model->unguard(); if(!$this->useInterface) $this->doImportWithoutInterface($Activities);
        else foreach($Activities as $activity){
            $this->primaryKeys = $activity['primary_key']; $insertResult = [];
            $this->setImportInteractObjectProperties($activity); $this->getCallMethod($this->object,SYNCHelper::$pre_import,[$activity]);
            if(!empty($activity['data'])){
                if(count($activity['data']) > 300) set_time_limit(ceil(count($activity['data'])/10));
                foreach($activity['data'] as $record){
                    if($this->doNeedToSkipThisImport($record)) continue;
                    $id = call_user_func_array([$this->object,SYNCHelper::$method_get_primary_id],[$record]);
                    if($id) $this->doUpdateImportRecord($id,$record);
                    else $insertResult[$this->getPrimaryKeyCode($this->primaryKeys,$record)] = $this->doInsertImportRecord($record);
                }
            }
            $this->getCallMethod($this->object,SYNCHelper::$post_import,[$activity,$insertResult]);
        }
    }
    private function doImportWithoutInterface($Activities){
        foreach($Activities as $activity){
            if(!empty($activity['data'])) foreach($activity['data'] as $record){
                if($this->model->find($record['id'])) $this->doUpdateImportRecord($record['id'],$record);
                else $this->doInsertImportRecord($record);
            }
        }
    }
    private function setImportInteractObjectProperties($content){
        $this->importInteractObjectAttributes = $this->getCallMethod($this->object,SYNCHelper::$method_import_attributes);
        $this->importInteractObjectMappings = $this->getCallMethod($this->object,SYNCHelper::$method_import_mappings);
        foreach($content as $Key => $Value) if(property_exists($this->object,$Key)) $this->object->$Key = $Value;
    }
    private function doNeedToSkipThisImport($record){
        if(method_exists($this->object,SYNCHelper::$method_get_is_valid)){
            return !call_user_func([$this->object,SYNCHelper::$method_get_is_valid],$record);
        } return false;
    }
    private function updateInteractObjectMode($mode){ if(property_exists($this->object,'mode')) $this->object->mode = $mode; }
    private function doInsertImportRecord($record){
        if($this->useInterface){
            $this->updateInteractObjectMode('create');
            $newModel = $this->model->create($this->getFilledAttributes($this->importInteractObjectAttributes,$this->importInteractObjectMappings,$record));
            $this->getCallMethod($this->object,SYNCHelper::$method_imported,[$record,$newModel->getKey()]);
        } else {
            $newModel = $this->model->create($record);
        }
        $this->updateClientTableRecordDate('created',$newModel->created_at->toDateTimeString());
        return $newModel->getKey();
    }
    private function doUpdateImportRecord($ID,$record){
        $selectedModel = $this->model->find($ID);
        if(!$selectedModel) return;
        if($this->useInterface){
            $this->updateInteractObjectMode('update');
            $selectedModel->forceFill($this->getFilledAttributes($this->importInteractObjectAttributes,$this->importInteractObjectMappings,$record))->save();
            $this->getCallMethod($this->object,SYNCHelper::$method_imported,[$record,$selectedModel->getKey()]);
        } else {
            $selectedModel->forceFill($record)->save();
        }
        $this->updateClientTableRecordDate('created',$selectedModel->updated_at->toDateTimeString());
    }
}
