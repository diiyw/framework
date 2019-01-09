<?php

namespace thinker {

    class Filter
    {
        const REQUIRED = "required";

        const LENGTH_MAX = "max length";

        const LENGTH_MIN = "min length";

        const MATCH = "match";

        const COMPARE = "compare";

        const FUNCTION = "function";

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
                        case self::COMPARE:
                            if ($value != $params[2]) {
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
                            if (function_exists($params[2])) {
                                $value = $params[2]($value);
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