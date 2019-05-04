<?php

namespace Milestone\Interact;

use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function index($client,$table){
        return SYNC::client($client,$table)->get();
    }
    public function delete(Request $request){ if($request->has('client')); SYNC::delete($request->client); }
}
