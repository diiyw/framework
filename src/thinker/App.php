<?php

namespace thinker {

    class App
    {

        /**
         * PATH_INFO
         * @var string
         */
        public static $pathInfo;

        /**
         * 请求模块
         * @var string
         */
        public static $module;

        /**
         * 请求控制器
         * @var string
         */
        public static $controller;

        /**
         * 插件目录
         * @var string
         */
        public static $pluginPath;

        /**
         * ROOT目录
         * @var string
         */
        public static $rootPath;

        /**
         * 站点目录
         * @var string
         */
        public static $publicPath;

        /**
         * 应用目录
         * @var string
         */
        public static $projectPath;

        /**
         * Application constructor.
         */
        public static function run()
        {
            defined("DS") || define("DS", DIRECTORY_SEPARATOR);
            self::parseUri();
            $wwwPath = empty($_SERVER["DOCUMENT_ROOT"]) ? $_SERVER["PWD"] : $_SERVER["DOCUMENT_ROOT"];
            self::$rootPath = dirname($wwwPath);
            self::$publicPath = $wwwPath;
            self::$projectPath = self::$rootPath . DS . "app";
            self::$pluginPath = self::$rootPath . DS . "plugin";
            // 自动加载
            spl_autoload_register(function ($class) {
                $modulePath = self::$projectPath . DS . "modules" . DS;
                $file = $modulePath . $class . ".php";
                if (file_exists($file)) {
                    include_once $file;
                }
            });
            Plugin::load(App::$pluginPath);
            Plugin::beforeDispatch();
            // 执行脚本
            $class = self::$module . "\\controller\\" . ucfirst(self::$controller);
            new $class();
        }

        /**
         * 解析PATH_INFO
         */
        private static function parseUri()
        {
            if (isset($_SERVER["REQUEST_URI"])) {
                $uri = explode("/", explode("?", trim($_SERVER["REQUEST_URI"], "/"))[0]);
            } else {
                $uri = array_slice($_SERVER["argv"], 1);
            }
            self::$pathInfo = join("/", $uri);
            self::$module = array_shift($uri);
            if (!self::$module) {
                self::$module = "home";
            }
            self::$controller = array_shift($uri);
            if (!self::$controller) {
                self::$controller = "Index";
            }
            //多余PATH_INFO解析为get请求参数
            foreach ($uri as $k => $v) {
                if (isset($uri[$k + 1])) {
                    $_REQUEST[$v] = $_GET[$v] = $uri[$k + 1];
                    unset($uri[$k + 1]);
                }
            }
        }

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
         * 添加配置项
         * @param $name
         * @param $obj
         * @return Plugin|View|Request|Object
         */
        public static function setConfig($path, $keyValue)
        {
            self::$config[$path] = $keyValue;
            return self::$config[$path];
        }

        /**
         * 加载配置
         * @param $name
         * @param string $key
         * @return array|mixed
         */
        public static function loadConfig($path, $key = "")
        {
            list($module, $filename) = explode(".", $path);
            if (!isset(self::$config[$path])) {
                $module = self::$projectPath . "/modules/" . $module;
                self::$config[$path] = include_once $module . "/config/" . $filename . ".php";
            }
            if (!empty($key) && isset(self::$config[$path][$key])) {
                return self::$config[$path][$key];
            }
            return self::$config[$path];
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

        /**
         * 监听事件
         * @param $name
         * @param $data
         */
        public function listen($name, $data)
        {

        }
    }
}