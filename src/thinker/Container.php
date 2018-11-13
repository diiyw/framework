<?php

namespace thinker {

    class Container
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
         * 注册对象,同时返回注册的对象
         * @param $name
         * @param $obj
         * @return mixed
         */
        public static function set($name, $obj)
        {
            self::$objects[$name] = $obj;
            return self::$objects[$name];
        }

        /**
         * 加载配置
         * @param $name
         * @return array|mixed
         */
        public static function loadConfig($name)
        {
            if (!isset(self::$config[$name])) {
                self::$config[$name] = include_once self::load("request")->projectPath . "/config/" . $name . ".php";
            }
            return self::$config[$name];
        }

        /**
         * 取对象
         * @param $name
         * @return Request|Response|View
         */
        public static function load($name)
        {
            if (empty(self::$objects[$name])) {
                return null;
            }
            return self::$objects[$name];
        }
    }
}

