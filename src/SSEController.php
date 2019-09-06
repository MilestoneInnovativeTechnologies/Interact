<?php

namespace Milestone\Interact;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SSEController extends Controller
{
    private $headers = [
        'Content-Type' => 'text/event-stream',
        'X-Accel-Buffering' => 'no',
        'Cache-Control' => 'no-cache',
    ];
    private $refresh = 10, $client = null, $tables = [];

    public function index($client){
        $this->client = $client;
        $this->tables = request('tables') ?: [];
        $response = new StreamedResponse(function(){
            while(true) {
                echo $this->getResponse();
                ob_flush(); flush();
                sleep(request('refresh') ?: $this->refresh);
            }
        });
        return $this->setHeaders($response);
    }

    private function getResponse(){
        $tables = $this->updatedTables();
        return $this->getSSEData('message',json_encode($tables));
    }

    private function updatedTables(){
        $updatedTables = [];
        foreach ($this->tables as $table){
            if($this->haveNew($table)) $updatedTables[] = $table;
        }
        return $updatedTables;
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
        return ($date[1]->greaterThan($sync) || $date[2]->greaterThan($sync));
    }

    private function getSyncInfo($client, $table){
        return SYNC::client($client,$table)->get();
    }

    private function getDates($data){
        $created = Arr::get($data,"table.created") ?: '1900-01-01 00:00:01';
        $updated = Arr::get($data,"table.updated") ?: '1900-01-01 00:00:01';
        $sync = Arr::get($data,"client.sync") ?: '1900-01-01 00:00:01';
        return [Carbon::createFromTimeString($sync),Carbon::createFromTimeString($created),Carbon::createFromTimeString($updated)];
    }
}