<?php

    namespace Milestone\Interact;

    use SimpleXMLElement;

    class Out
    {
        private $data = null;
        private $type = null;
        private $format = null;

        private $XML_parent_node = 'inventory';
        private $XML_numeric_prefix = 'id';

        public function __construct($data)
        {
            $this->format = request()->get('format','json');
            $this->type = request()->get('type','data');
            $this->data = $data;
        }

        public static function data($data){
            $Out = new self($data);
            $method_format = 'prepare_' . $Out->format;
            $method_type = 'response_' . $Out->type;
            return call_user_func_array([$Out,$method_type],[call_user_func([$Out,$method_format],$Out->data),$Out->format]);
        }

        public function prepare_json($data){
            return trim(json_encode($data));
        }

        public function prepare_xml($data){
            $xml = new SimpleXMLElement('<' . $this->XML_parent_node . '/>');
            $this->array_to_xml($data,$xml);
            return trim($xml->asXML());
        }

        private function array_to_xml($array, &$xml) {
            foreach($array as $key => $value) {
                if(is_array($value)) {
                    $nodeKey = (is_numeric($key)) ? $this->XML_numeric_prefix . $key : $key;
                    $newNode = $xml->addChild($nodeKey);
                    $this->array_to_xml($value,$newNode);
                } else $xml->addChild($key, $value);
            }
        }

        public function response_data($data){
            return $data;
        }

        public function response_file($data,$format){
            return response()->streamDownload(function() use($data){
                echo $data;
            },'data.' . $format);
        }

    }