<?php


    namespace Milestone\Interact;


    class SYNCHelper
    {
        public static $method_get_attributes = 'getExportAttributes';
        public static $method_get_mappings = 'getExportMappings';
        public static $method_import_attributes = 'getImportAttributes';
        public static $method_import_mappings = 'getImportMappings';

        public static $method_is_valid_get = 'isValidExportGet';
        public static $method_is_valid_update = 'isValidExportUpdate';
        public static $method_get_is_valid = 'isValidImportRecord';
        public static $method_get_exported = 'recordGetExported';
        public static $method_update_exported = 'recordUpdateExported';
        public static $method_imported = 'recordImported';

        public static $pre_export_get = 'preExportGet';
        public static $post_export_get = 'postExportGet';
        public static $pre_export_update = 'preExportUpdate';
        public static $post_export_update = 'postExportUpdate';
        public static $pre_import = 'preImport';
        public static $post_import = 'postImport';

        public static $method_get_primary_id = 'getPrimaryIdFromImportRecord';


        public static function wrapWithActivityProperties($table,$mode,$data){
            $primary_key = [];
            return compact('table','primary_key','mode','data');
        }

        public static function newlyCreatedRecordsFetchQuery($Model,$Created){
            return $Model->query()
                ->where(function($Q) use($Model,$Created){ $Q->where($Model->getCreatedAtColumn(),'>',$Created); })
                ->orderBy($Model->getCreatedAtColumn(),'asc');
        }

        public static function newlyUpdatedRecordsFetchQuery($Model,$Created,$Updated){
            return $Model->query()
                ->where(function($Q) use($Model,$Created,$Updated){ $Q->where($Model->getUpdatedAtColumn(),'>',$Updated)->where($Model->getCreatedAtColumn(),'<=',$Created); })
                ->orderBy($Model->getCreatedAtColumn(),'asc');
        }
    }