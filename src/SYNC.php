<?php

    namespace Milestone\Interact;

    use Illuminate\Support\Arr;
    use Illuminate\Support\Facades\Storage;

    class SYNC
    {
        private $sync = null;
        private $disk, $table, $client;
        public static $file = 'sync';

        public function __construct($client,$table = null)
        {
            $this->table = $table ?: $client; $this->client = $table ? $client : null;
            $disk = $this->disk = self::disk();
            $json_data = Storage::disk($disk)->exists(self::$file)
                ? Storage::disk($disk)->get(self::$file)
                : $this->createAndGetSyncData();
            $this->sync = json_decode($json_data,true);
            $this->correctSyncData();
        }

        static public function table($table){ return new self($table); }
        static public function client($client,$table = null){ return new self($client,$table); }
        static public function delete($client){ return (new self($client,$temp_table = '_OO_'))->deleteKey("clients.{$client}","tables.{$temp_table}"); }
        static public function disk(){ return array_keys(config('interact.filesystems_disks'))[0]; }
        public function setTable($table){ return new self($this->client,$table); }
        public function setClient($client){ return new self($client,$this->table); }
        public function setCreated($table,$record){ $this->setTableOfType($table,$record,'created',!!$this->client); }
        public function setUpdated($table,$record){ $this->setTableOfType($table,$record,'updated',!!$this->client); }
        public function get(){
            $table = Arr::get($this->sync,"tables.{$this->table}");
            if($this->client) $client = Arr::get($this->sync,"clients.{$this->client}.{$this->table}");
            return compact('table','client');
        }

        private function getTemplateRequestItem(){
            $client = $this->client; $table = $this->table;
            if(!$table && $client) return ['client',$client];
            if($table && $client) return ['full',$client,$table];
            if($table && !$client) return ['table',$table];
            return 'base';
        }

        private function template($item = 'base',$client = null, $table = null){
            $base = ['tables'=>[],'clients' =>[]];
            $time = ['created'=>0,'updated'=>0,'record'=>['created'=>0,'updated'=>0]];
            if($item === 'table') return [ 'tables' => [ $client => $time ], 'clients' => [] ];
            if($item === 'full') return [ 'tables' => [ $table => $time ], 'clients' => [ $client => [ $table => $time ] ] ];
            if($item === 'client') return [ 'tables' => [], 'clients' => [ $client => [] ] ];
            if($item === 'time') return $time;
            return $base;
        }

        private function setTableOfType($table, $record, $type='created', $client=false){
            $this->updateSync($this->getArrKey($type,$client,false),$table);
            $this->updateSync($this->getArrKey($type,$client,true),$record);
        }

        private function getArrKey($type,$client, $record){ return implode('.',$this->getArrForKey($type,$client,$record)); }
        private function getArrForKey($type, $client, $record){
            $Array = [$type]; if($record) array_unshift($Array,'record');
            array_unshift($Array,$this->table);
            if($client) array_unshift($Array,'clients',$this->client);
            else array_unshift($Array,'tables');
            return $Array;
        }

        private function createAndGetSyncData(){
            $this->sync = call_user_func_array([$this,'template'],$this->getTemplateRequestItem());
            $this->correctSyncData(); $this->storeSync(); return json_encode($this->sync);
        }

        private function correctSyncData(){
            $sync = $this->sync;
            if($this->table && !Arr::get($sync,"tables.{$this->table}")) $this->updateSync("tables.{$this->table}",$this->template('time'));
            if($this->client && !$this->table && !Arr::get($sync,"clients.{$this->client}")) $this->updateSync("clients.{$this->client}",[]);
            if($this->client && $this->table && !Arr::get($sync,"clients.{$this->client}")) $this->updateSync("clients.{$this->client}",[$this->table => $this->template('time')]);
            if($this->client && $this->table && !Arr::get($sync,"clients.{$this->client}.{$this->table}")) $this->updateSync("clients.{$this->client}.{$this->table}",$this->template('time'));
        }

        private function updateSync($key,$value){ Arr::set($this->sync,$key,$value); $this->storeSync(); }
        private function deleteKey($key,$table = null){ Arr::forget($this->sync,[$key,$table]); return !!$this->storeSync(); }
        private function storeSync(){ Storage::disk($this->disk)->put(self::$file,json_encode($this->sync)); return $this->sync; }
    }