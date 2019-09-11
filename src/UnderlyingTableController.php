<?php

namespace Milestone\Interact;

use Illuminate\Support\Facades\Cache;

class UnderlyingTableController extends Controller
{

    public static $cache_store = 'uTable';

    public static function table($table){
        $underlyingTable = Cache::store(self::$cache_store)->rememberForever($table,function() use($table){
            $Cls = new self(); $Cls->initSync($table);
            return $Cls->model->getTable();
        });
        return $underlyingTable;
    }
}
