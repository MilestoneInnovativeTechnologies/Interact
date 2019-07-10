<?php

    namespace Milestone\Interact;

    class NameConflict
    {
        public static $keywords = [
            'function'
        ];

        public static $suffix = 'R';

        public static function conflict($name){
            return (in_array($name,self::$keywords)) ? ($name . self::$suffix) : $name;
        }

    }