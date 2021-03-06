<?php

    namespace Milestone\Interact;

    use Illuminate\Support\Arr;
    use Illuminate\Support\Facades\Storage;

    class SYNC
    {
        private $sync = null;
        private static $disk, $table, $client;
        public static $file = 'sync';

        public function __construct($client,$table = null)
        {
            self::$table = $table ?: $client; self::$client = $table ? $client : null;
            $disk = self::$disk = self::disk();
            $json_data = Storage::disk($disk)->exists(self::file())
                ? Storage::disk($disk)->get(self::file())
                : $this->createAndGetSyncData();
            $this->sync = json_decode($json_data,true);
            $this->correctSyncData();
        }

        static public function file($table = null){ return implode('.',[$table ?: self::$table,request()->getHost(),self::$file]); }
        static public function table($table){ return new self($table); }
        static public function client($client,$table = null){ return new self($client,$table); }
        static public function delete($client, $table = '_OO_'){ return (new self($client,$table))->deleteKey("clients.{$client}"); }
        static public function disk(){ return array_keys(config('interact.filesystems_disks'))[0]; }
        public function setTable($table){ return new self(self::$client,$table); }
        public function setClient($client){ return new self($client,self::$table); }
        public function setCreated($table,$record){ $this->setTableOfType($table,$record,'created',!!self::$client); }
        public function setUpdated($table,$record){ $this->setTableOfType($table,$record,'updated',!!self::$client); }
        public function setSync($dtz = null){ $this->updateSync($this->getArrKey('sync',self::$client,false),$dtz ?: now()->toDateTimeString()); return null; }
        public function get(){
            $table = Arr::get($this->sync,"tables." . self::$table);
            if(self::$client) $client = Arr::get($this->sync,"clients.{$this::$client}.{$this::$table}");
            return compact('table','client');
        }

        private function getTemplateRequestItem(){
            $client = self::$client; $table = self::$table;
            if(!$table && $client) return ['client',$client];
            if($table && $client) return ['full',$client,$table];
            if($table && !$client) return ['table',$table];
            return 'base';
        }

        private function template($item = 'base',$client = null, $table = null){
            $base = ['tables'=>[],'clients' =>[]];
            $time = ['created'=>0,'updated'=>0,'record'=>['created'=>0,'updated'=>0]];
            $ctime = array_merge($time,['sync'=>0]);
            if($item === 'table') return [ 'tables' => [ $client => $time ], 'clients' => [] ];
            if($item === 'full') return [ 'tables' => [ $table => $time ], 'clients' => [ $client => [ $table => $ctime ] ] ];
            if($item === 'client') return [ 'tables' => [], 'clients' => [ $client => [] ] ];
            if($item === 'time') return $time;
            return $base;
        }

        private function setTableOfType($table, $record, $type='created', $client=false){
            $this->updateSync($this->getArrKey($type,$client,false),$table);
            $this->updateSync($this->getArrKey($type,$client,true),$record);
            $this->setSync();
        }

        private function getArrKey($type,$client,$record){ return implode('.',$this->getArrForKey($type,$client,$record)); }
        private function getArrForKey($type, $client, $record){
            $Array = [$type]; if($record) array_unshift($Array,'record');
            array_unshift($Array,self::$table);
            if($client) array_unshift($Array,'clients',self::$client);
            else array_unshift($Array,'tables');
            return $Array;
        }

        private function createAndGetSyncData(){
            $this->sync = call_user_func_array([$this,'template'],$this->getTemplateRequestItem());
            $this->correctSyncData(); $this->storeSync(); return json_encode($this->sync);
        }

        private function correctSyncData(){
            $sync = $this->sync;
            if(self::$table && !Arr::get($sync,"tables.{$this::$table}")) $this->updateSync("tables.{$this::$table}",$this->template('time'));
            if(self::$client && !self::$table && !Arr::get($sync,"clients.{$this::$client}")) $this->updateSync("clients.{$this::$client}",[]);
            if(self::$client && self::$table && !Arr::get($sync,"clients.{$this::$client}")) $this->updateSync("clients.{$this::$client}",[$this::$table => $this->template('time')]);
            if(self::$client && self::$table && !Arr::get($sync,"clients.{$this::$client}.{$this::$table}")) $this->updateSync("clients.{$this::$client}.{$this::$table}",$this->template('time'));
        }

        private function updateSync($key,$value){ Arr::set($this->sync,$key,$value); $this->storeSync(); }
        private function deleteKey($key){ Arr::forget($this->sync,$key); return !!$this->storeSync(); }
        private function storeSync(){ Storage::disk($this::$disk)->put(self::file(),json_encode($this->sync)); return $this->sync; }
    }
