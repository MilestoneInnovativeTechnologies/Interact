<?php

namespace Milestone\Interact;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SSEController extends Controller
{
    private $headers = [
        'Content-Type' => 'text/event-stream',
        'X-Accel-Buffering' => 'no',
        'Cache-Control' => 'no-cache',
    ];
    private $refresh = 10, $client = null, $tables = [], $count = 0, $max = 30;

    public function index($client){
        $this->refresh = request('refresh') ?: $this->refresh;
        $this->max = request('max') ?: $this->max;
        $this->client = $client;
        $this->tables = request('tables') ?: [];
        $response = new StreamedResponse(function(){
            while(true) {
                set_time_limit($this->refresh + 5);
                $this->flushResponse();
                if($this->count++ > $this->max) exit(200);
                sleep($this->refresh);
            }
        });
        return $this->setHeaders($response);
    }

    public function ajax($client){
        $this->client = $client;
        $this->tables = request('tables') ?: [];
        return response($this->updatedTables(),200,['Access-Control-Allow-Origin' => '*','Content-Type' => 'application/json']);
    }

    private function flushResponse(){
        echo $this->getResponse();
        ob_flush(); flush();
    }

    private function getResponse(){
        $tables = $this->updatedTables();
        return $this->getSSEData('message',json_encode($tables));
    }

    private function updatedTables(){
        return array_values(array_filter($this->tables,function($table){ return $this->haveNew($table); }));
    }

    private function getSSEData($event,$data){
        return implode("\n",[
            "id: " . microtime(true) * 10000,
            "event: $event",
            "data: $data",
        ]) . "\n\n";
    }

    private function setHeaders($response){
        foreach ($this->headers as $header => $value)
            $response->headers->set($header, $value);
        return $response;
    }

    private function haveNew($table){
        $info = $this->getSyncInfo($this->client,$table);
        $date = $this->getDates($info); $sync = $date[0];
        return ($date[1]->greaterThanOrEqualTo($sync) || $date[2]->greaterThanOrEqualTo($sync));
    }

    private function getSyncInfo($client, $table){
        $underlyingTable = UnderlyingTableController::table($table);
        return SYNC::client($client,$underlyingTable)->get();
    }

    private function getDates($data){
        $created = Arr::get($data,"table.created",'1900-01-01 00:00:01');
        $updated = Arr::get($data,"table.updated",'1900-01-01 00:00:01');
        $sync = Arr::get($data,"client.sync",'1900-01-01 00:00:01');
        return [Carbon::createFromTimeString($sync),Carbon::createFromTimeString($created),Carbon::createFromTimeString($updated)];
    }
}
