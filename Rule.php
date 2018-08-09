<?php

namespace thinker;

class Rule
{
    public

        /**
         * 待验证数据
         * @var void
         */
        $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * 必须字段
     * @param $message
     * @return mixed
     * @throws \Exception
     */
    public function required($message)
    {
        if (empty($this->data)) {
            throw new \Exception($message);
        }
        return $this->data;
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
        if (preg_match($pattern, $this->data)) {
            return $this->data;
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
            if (mb_strlen($this->data) < $v) {
                throw new \Exception($message);
            }
        }
        if ($type == "max") {
            if (mb_strlen($this->data) > $v) {
                throw new \Exception($message);
            }
        }
        return $this->data;
    }

    /**
     * 比较两个值
     * @param Rule $v
     * @param $message
     * @return mixed
     * @throws \Exception
     */
    public function compare($v, $message)
    {
        if ($this->data != $v->data) {
            throw new \Exception($message);
        }
        return $this->data;
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
        if (!$func($this->data)) {
            throw new \Exception($message);
        }
        return $this->data;
    }
}