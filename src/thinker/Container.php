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
         * @return Plugin|View|Request|Object
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
        public static function loadConfig($name, $key = "")
        {
            if (!isset(self::$config[$name])) {
                $request = self::load("request");
                $module = $request->projectPath . "/modules/" . $request->module;
                self::$config[$name] = include_once $module . "/config/" . $name . ".php";
            }
            return isset(self::$config[$name][$key]) ? self::$config[$name][$key] : "";
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

