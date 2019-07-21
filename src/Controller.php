<?php

namespace Milestone\Interact;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as BaseController;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class Controller extends BaseController
{
    public $upload_file_name = 'file';
    public $base64_content_name = 'content';

    public $method_get_model = 'getModel';
    public $method_get_primary_id = 'getPrimaryIdFromImportRecord';

    public $object, $model;

    private $cache_store = 'interact';
    public $model_updated,$last_checked;
    private $max_same_request = 0;

    public function __construct()
    {
        $this->max_same_request = config('interact.max_same_request');
        $this->cache_store = array_keys(config('interact.cache_stores'))[0];
    }

    public function getUploadedContent(){
        if(request()->has($this->base64_content_name)) return $this->getUploadedBase64Content();
        if(request()->has($this->upload_file_name)) return $this->getUploadedFileContent();
        return [];
    }

    public function getUploadedBase64Content(){
        if(!request()->has($this->base64_content_name)) return [];
        $contents = base64_decode(request()->get($this->base64_content_name));
        return json_decode($contents,true);
    }

    public function getUploadedFileContent(){
        if(!request()->has($this->upload_file_name)) return [];
        $file = request()->file($this->upload_file_name);
        $contents = file_get_contents($file);
        return json_decode($contents,true);
    }

    public function initImport($content){
        $table = $content['table'];
        $this->object = $Object = $this->getTableObject($table);
        $this->model = $Model = $this->getModel($Object);
    }

    public function initExport($table){
        $this->object = $Object = $this->getTableObject($table);
        $model_class = $this->getCallMethod($Object,$this->method_get_model);
        $this->setTimings($model_class); $status = $this->incrementAndGetPingStatus($model_class);
        $this->model = $status ? $this->getModel($Object) : null;
    }

    public function initSync($table){
        $this->object = $Object = $this->getTableObject($table);
        $this->model = $Model = $this->getModel($Object);
    }

    public function getTableObject($table){ return $this->getObject($this->getTableClass($table)); }
    private function getObject($class){ return new $class; }
    private function getModel($object){ return $this->getObject($this->getCallMethod($object,$this->method_get_model)); }
    private function getTableClass($table){ return config('interact.namespace') . "\\" . NameConflict::conflict($table); }
    public function getPrimaryId($record){ return call_user_func_array([$this->object,$this->method_get_primary_id],[$record]); }
    public function getPrimaryKeyCode($pks,$record){
        if(!$pks || empty($pks)) return microtime(true)*1000000000;
        return is_array($pks) ? implode(config('interact.delimiter'),Arr::only($record,$pks)) : Arr::get($record,$pks);
    }

    public function getCallMethod($object,$method,$attrs = []){
        if(method_exists($object,$method))
            return call_user_func_array([$object,$method],$attrs);
        return null;
    }

    public function getFilledAttributes($attributes,$mappings,$record){
        $attributes = (array) $attributes; if(!$record || empty($record)) return [];
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
        if(is_callable([$this->object,$map])) return call_user_func_array([$this->object,$map],[$record]);
        return null;
    }

    private function setTimings($model_class){
        $checked = $model_class . '_checked'; $check_count = $model_class . '_count';
        $this->model_updated = cache()->store($this->cache_store)->rememberForever($model_class,function(){ return Carbon::create(1900); });
        $this->last_checked = cache()->store($this->cache_store)
            ->rememberForEver($checked,function()use($check_count){
                cache()->store($this->cache_store)->put($check_count,0);
                return Carbon::create(1900);
            });
        $count = cache()->store($this->cache_store)->increment($check_count);
        return $this->last_checked->lessThanOrEqualTo($this->model_updated) || $count > $this->max_same_request;
    }

    private function incrementAndGetPingStatus($model_class){
        $check_count = $model_class . '_count';
        $count = cache()->store($this->cache_store)->increment($check_count);
        return $this->last_checked->lessThanOrEqualTo($this->model_updated) || $count > $this->max_same_request || $count === 0;
    }

    public function updateTimings($table){
        $this->object = $Object = $this->getTableObject($table);
        $model_class = $this->getCallMethod($Object,$this->method_get_model);
        $checked = $model_class . '_checked'; $check_count = $model_class . '_count';
        cache()->store($this->cache_store)->put($checked,now());
        cache()->store($this->cache_store)->put($check_count,0);
    }

}
