<?php

namespace thinker;

class Value
{
    private

        /**
         * 待验证数据
         * @var void
         */
        $_value;

    public function __construct($value)
    {
        $this->_value = $value;
    }

    /**
     * 必须字段
     * @param $message
     * @return mixed
     * @throws \Exception
     */
    public function required($message)
    {
        if (empty($this->_value)) {
            throw new \Exception($message);
        }
        return $this->_value;
    }

    /**
     * 正则校验
     * @param $pattern
     * @param $message
     * @return mixed
     * @throws \Exception
     */
    public function match($pattern, $message)
    {
        if (preg_match($pattern, $this->_value)) {
            return $this->_value;
        }
        throw new \Exception($message);
    }

    /**
     * 长度校验
     * @param $type
     * $type = "min"|"max"
     * @param $v
     * @param $message
     * @return mixed
     * @throws \Exception
     */
    public function length($type, $v, $message)
    {
        if ($type == "min") {
            if (mb_strlen($this->_value) < $v) {
                throw new \Exception($message);
            }
        }
        if ($type == "max") {
            if (mb_strlen($this->_value) > $v) {
                throw new \Exception($message);
            }
        }
        return $this->_value;
    }

    /**
     * 比较两个值
     * @param Value $v
     * @param $message
     * @return mixed
     * @throws \Exception
     */
    public function compare($v, $message)
    {
        if ($this->_value != $v->_value) {
            throw new \Exception($message);
        }
        return $this->_value;
    }

    /**
     * 自定义校验
     * @param callable $func
     * @param $message
     * @return mixed
     * @throws \Exception
     */
    public function callback(callable $func, $message)
    {
        if (!$func($this->_value)) {
            throw new \Exception($message);
        }
        return $this->_value;
    }
}