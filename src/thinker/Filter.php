<?php

namespace thinker {

    class Filter
    {
        const REQUIRED = 1;

        const LEN_MAX = 2;

        const LEN_MIN = 3;

        const MATCH = 4;

        const COMPARE = 5;

        const CUSTOM = 6;

        const RENAME = 7;

        const REMOVE = 8;

        const PROCESS = 9;

        const KEEP = 10;

        public $data = array();

        public $error = "";

        public function __construct($data, $method = "get")
        {
            $rules = method_exists($this, $method) ? $this->$method() : [];
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
                        case self::KEEP:
                            if (isset($params[1])) {
                                if (empty($value)) {
                                    $value = $params[1];
                                }
                            }
                            break;
                        case self::LEN_MAX:
                            $length = mb_strlen($value);
                            if ($length > $params[2]) {
                                $this->error = $params[1];
                                return;
                            }
                            break;
                        case self::LEN_MIN:
                            $length = mb_strlen($value);
                            if ($length < $params[2]) {
                                $this->error = $params[1];
                                return;
                            }
                            break;
                        case self::RENAME:
                            $value = $this->data[$name];
                            unset($this->data[$name]);
                            $name = $params[1];
                            break;
                        case self::REMOVE:
                            unset($this->data[$name]);
                            continue 2;
                        case self::COMPARE:
                            $value2 = $data[$params[2]] ?? $params[2];
                            if ($value != $value2) {
                                $this->error = $params[1];
                                return;
                            }
                            break;
                        case self::MATCH:
                            if (!preg_match($params[2], $value)) {
                                $this->error = $params[1];
                                return;
                            }
                            break;
                        case self::CUSTOM:
                            if (is_callable($params[2])) {
                                if (!$params[2]($value)) {
                                    $this->error = $params[1];
                                    return;
                                }
                            }
                            break;
                        case self::PROCESS:
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