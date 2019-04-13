<?php


    namespace Milestone\Interact;


    class Method
    {

        public static function getModelClassFromEloquentName($name,$prefix = 'eloquent.created: '){
            return trim(ltrim($name,$prefix),"\\\/");
        }

    }