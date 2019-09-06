<?php

namespace Milestone\Interact;

class UnderlyingTableController extends Controller
{
    public static function table($table){
        $Cls = new self(); $Cls->initSync($table);
        return $Cls->model->getTable();
    }
}
