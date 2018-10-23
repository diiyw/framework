<?php

namespace thinker;

class Registry
{
    /**
     * 配置
     * @var array
     */
    private static $config;

    /**
     * 对象集合
     * @var array
     */
    private static $objects;

    /**
     * 注册对象
     * @param $name
     * @param $obj
     */
    public static function set($name, $obj)
    {
        self::$objects[$name] = $obj;
    }

    /**
     * 加载配置
     * @param $name
     * @return array|mixed
     */
    public static function loadConfig($name)
    {
        if (!isset(self::$config[$name])) {
            self::$config[$name] = include_once self::get("request")->projectPath . "/config/" . $name . ".php";
        }
        return self::$config[$name];
    }

    /**
     * 取对象
     * @param $name
     * @return Request|Response|Input|Plugin|View
     */
    public static function get($name, $method = "")
    {
        if (empty(self::$objects[$name])) {
            return null;
        }
        if (!empty($method)) {
            return self::$objects[$name]->$method();
        }
        return self::$objects[$name];
    }
}