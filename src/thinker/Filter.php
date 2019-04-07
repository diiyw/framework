<?php

namespace thinker {

    class Filter
    {
        // 必须传值
        const REQUIRED = 1;

        // 验证最大长度
        const LEN_MAX = 2;

        // 验证最短长度
        const LEN_MIN = 3;

        // 正则匹配
        const MATCH = 4;

        // 两值比较
        const COMPARE = 5;

        const CUSTOM = 6;

        // 字段重命名
        const RENAME = 7;

        // 移除字段
        const REMOVE = 8;

        // 自定义值处理方法
        const PROCESS = 9;

        // 保留字段，并可以设置默认值
        const KEEP = 10;

        public $data = array();

        public $error = "";

        public function __construct($data, $method = "get")
        {
            $rules = method_exists($this, $method) ? $this->$method() : [];
            foreach ($rules as $name => $rule) {
                if (empty($rule) && isset($data[$name])) {
                    $this->data[$name] = $data[$name];
                    continue;
                }
                foreach ($rule as $method => $params) {
                    $value = $data[$name] ?? "";
                    switch ($method) {
                        // $params是错误提示信息
                        case self::REQUIRED:
                            if ($value === "") {
                                $this->error = $params;
                                return;
                            }
                            break;
                        // $params 是默认值
                        case self::KEEP:
                            if (empty($value)) {
                                $value = $params;
                            }
                            break;
                        // $params[0] 是长度，$params[1]是错误信息
                        case self::LEN_MAX:
                            $length = mb_strlen($value);
                            if ($length > $params[1]) {
                                $this->error = $params[0];
                                return;
                            }
                            break;
                        // $params[0] 是长度，$params[1]是错误信息
                        case self::LEN_MIN:
                            $length = mb_strlen($value);
                            if ($length < $params[1]) {
                                $this->error = $params[0];
                                return;
                            }
                            break;
                        // $params 重命名的名称
                        case self::RENAME:
                            $value = $this->data[$name];
                            unset($this->data[$name]);
                            $name = $params;
                            break;
                        // $params 任意值可以是true
                        case self::REMOVE:
                            unset($this->data[$name]);
                            continue 2;
                        // $params[0] 是比较字段，$params[1]是错误信息
                        case self::COMPARE:
                            $value2 = $data[$params[1]] ?? $params[1];
                            if ($value != $value2) {
                                $this->error = $params[0];
                                return;
                            }
                            break;
                        // $params[0] 是正则表达式，$params[1]是错误信息
                        case self::MATCH:
                            if (!preg_match($params[0], $value)) {
                                $this->error = $params[1];
                                return;
                            }
                            break;
                        // $params[0] 是函数名称，$params[1]是错误信息
                        case self::CUSTOM:
                            if (is_callable($params[0])) {
                                if (!$params[0]($value)) {
                                    $this->error = $params[1];
                                    return;
                                }
                            }
                            break;
                        // $params 是函数名称
                        case self::PROCESS:
                            if (function_exists($params) || is_callable($params)) {
                                $value = $params($value);
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