<?php

namespace thinker;

class Input
{
    private

        /**
         * $_GET
         * @var array
         */
        $_get,

        /**
         * $_POST
         * @var array
         */
        $_post,

        /**
         * php://input
         * @var array
         */
        $_input;

    public function __construct()
    {
        $this->_get = $_GET;
        $this->_post = $_POST;
        if (in_array($_SERVER['REQUEST_METHOD'], ["PUT", "DELETE"])) {
            $this->_input = file_get_contents('php://input');
        }
    }

    /**
     * 返回$_GET数据
     * @param string $key
     * @return Value
     */
    public function get($key = "")
    {
        return new Value($this->_get[$key] ?? "");
    }

    /**
     * 返回$_POST数据
     * @param string $key
     * @return Value
     */
    public function post($key = "")
    {
        return new Value($this->_post[$key] ?? $this->_post[$key]);
    }

    /**
     * 返回php://input数据
     * @return Value
     */
    public function input()
    {
        return new Value($this->_input);
    }

    /**
     * XSS过滤
     * @param $data
     * @return array|string
     */
    private function removeXSS($data)
    {
        if (!is_array($data)) {
            if (mb_detect_encoding($data, "UTF-8")) {
                return htmlspecialchars(trim($data));
            }

            return htmlspecialchars(trim($data), ENT_QUOTES, 'GB2312');
        }
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $this->removeXSS($v);
            } else {
                $data[$k] = htmlspecialchars(trim($v));
            }
        }

        return $data;
    }
}