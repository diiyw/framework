<?php

namespace thinker {
    
    class DI
    {
        /**
         * ����
         * @var array
         */
        private static $config;

        /**
         * ���󼯺�
         * @var array
         */
        private static $objects;

        /**
         * ע�����,ͬʱ����ע��Ķ���
         * @param $name
         * @param $obj
         */
        public static function set($name, $obj)
        {
            self::$objects[$name] = $obj;
            return self::$objects[$name];
        }

        /**
         * ��������
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
         * ȡ����
         * @param $name
         * @return Request|Response|Input|Plugin|View
         */
        public static function load($name, $method = "")
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
}

