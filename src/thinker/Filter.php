<?php

namespace thinker {

    class Filter
    {
        const REQUIRED = 1;

        const LENGTH_MAX = 2;

        const LENGTH_MIN = 3;

        const MATCH = 4;

        const COMPARE = 5;

        const FUNCTION = 6;

        const RENAME = 7;

        const REMOVE = 8;

        public $data = array();

        public $error = "";

        public function __construct($data, $method = "get")
        {
            $rules = $this->$method();
            foreach ($rules as $name => $rule) {
                foreach ($rule as $params) {
                    $value = $data[$name] ?? "";
                    switch ($params[0]) {
                        case self::REQUIRED:
                            if (empty($value)) {
                                $this->error = $params[1];
                                return;
                            }
                            break;
                        case self::LENGTH_MAX:
                            $length = mb_strlen($value);
                            if ($length > $params[2]) {
                                $this->error = $params[1];
                                return;
                            }
                            break;
                        case self::LENGTH_MIN:
                            $length = mb_strlen($value);
                            if ($length < $params[2]) {
                                $this->error = $params[1];
                                return;
                            }
                            break;
                        case self::RENAME:
                            $name = $params[1];
                            break;
                        case self::REMOVE:
                            unset($this->data[$name]);
                            break;
                        case self::COMPARE:
                            $value2 = $data[$params[1]] ?? $params[1];
                            if ($value != $value2) {
                                $this->error = $params[1];
                                return;
                            }
                            break;
                        case self::MATCH:
                            if (preg_match('/' . $params[2] . '/', $value)) {
                                $this->error = $params[1];
                                return;
                            }
                            break;
                        case self::FUNCTION:
                            if (function_exists($params[1])) {
                                $value = $params[1]($value);
                            }
                            break;
                    }
                    $this->data[$name] = $value;
                }
            }
        }

        public function error()
        {
            return $this->error;
        }
    }
}