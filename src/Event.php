<?php

    namespace Milestone\Interact;

    use Illuminate\Support\Arr;
    use Illuminate\Support\Facades\Event as LEvent;

    class Event
    {
        public function __construct()
        {

            LEvent::listen(['eloquent.created: *'], function($name, $data){
                $class = $this->getClass($name); $model = new $class;
                $table = $model->getTable(); $record = Arr::get($data[0],$data[0]->getCreatedAtColumn(),self::defaultDateTime())->toDateTimeString();
                SYNC::table($table)->setCreated(self::nowDateTime(),$record);
            });
            LEvent::listen(['eloquent.updated: *'], function($name,$data){
                $class = $this->getClass($name); $model = new $class;
                $table = $model->getTable(); $record = Arr::get($data[0],$data[0]->getUpdatedAtColumn(),self::defaultDateTime())->toDateTimeString();
                SYNC::table($table)->setUpdated(self::nowDateTime(),$record);
            });
        }

        static public function register(){ return new self(); }

        static public function defaultDateTime(){ return now()->setYear(1900)->startOfCentury(); }
        static public function nowDateTime(){ return now()->toDateTimeString(); }
        private function getClass($name){ return preg_split("/[\s\.\:]+/",$name)[2]; }

    }
