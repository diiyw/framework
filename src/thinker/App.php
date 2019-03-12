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

        protected static $plugins;

        /**
         * Application constructor.
         */
        public static function run()
        {
            defined("DS") || define("DS", DIRECTORY_SEPARATOR);
            $wwwPath = empty($_SERVER["DOCUMENT_ROOT"]) ? $_SERVER["PWD"] : $_SERVER["DOCUMENT_ROOT"];
            self::$rootPath = dirname($wwwPath);
            self::$publicPath = $wwwPath;
            self::$projectPath = self::$rootPath . DS . "app";
            self::$pluginPath = self::$projectPath . DS . "plugins";
            // 自动加载
            spl_autoload_register(function ($class) {
                $dirs = array("modules", "plugins");
                foreach ($dirs as $dir) {
                    $file = self::$projectPath . DS . $dir . DS . $class . ".php";
                    if (file_exists($file)) {
                        include_once $file;
                        break;
                    }
                }
            });
            self::loadPlugins(self::$pluginPath);
            self::hook("beforeDispatch");
            self::parseUri();
            // 执行控制器
            $class = self::$module . "\\controller\\" . ucfirst(self::$controller);
            (new $class())->resolve();
        }

        /**
         * 解析PATH_INFO
         */
        public static function parseUri()
        {
            //解析
            if (isset($_SERVER["REQUEST_URI"])) {
                $uri = explode("/", explode("?", trim($_SERVER["REQUEST_URI"], "/"))[0]);
            } else {
                $uri = array_slice($_SERVER["argv"], 1);
            }
            $pathinfo = join("/", $uri);
            $module = array_shift($uri);
            $module = empty($module) ? "home" : $module;
            $moduleConst = $module . "\\" . $module . "Const";
            //路由重写
            $router = self::hook("router");
            if (class_exists($moduleConst)) {
                $router = array_merge($router, $moduleConst::ROUTERS);
            }
            foreach ($router as $pattern => $url) {
                $pattern = str_replace('(num)', '(\d*)', $pattern);
                if (preg_match('@' . $pattern . "@", $pathinfo)) {
                    $pathinfo = preg_replace('@' . $pattern . "@", $url, $pathinfo);
                    break;
                }
            }
            $uri = explode("/", $pathinfo);
            self::$pathInfo = $pathinfo;
            $module = array_shift($uri);
            self::$module = empty($module) ? "home" : $module;
            self::$controller = array_shift($uri) ?? "Index";
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
         * 加载插件
         * @param $folder
         */
        public static function loadPlugins($folder)
        {
            if (is_dir($folder)) {
                $handle = opendir($folder);
                while ($name = readdir($handle)) {
                    $file = $folder . DS . $name;
                    if (is_file($file)) {
                        $pathInfo = pathinfo($file);
                        if ($pathInfo["extension"] == "php") {
                            $entry = $name . "\\" . $pathInfo["filename"];
                            self::$plugins[$entry] = new $entry;
                        }
                        continue;
                    }
                    if ($name != "." && $name != ".." && is_dir($file)) {
                        if (file_exists($file . "/install.lock")) {
                            $info = $file . "/info.php";
                            $info = require_once $info;
                            foreach ($info["plugins"] as $plugin) {
                                $controller = "\\" . $name . "\\controller\\" . $plugin;
                                self::$plugins[] = new $controller;
                            }
                        }
                    }
                }
            }
        }

        /**
         * 监听钩子
         * @param $name
         * @param $data
         * @return mixed
         */
        public static function hook($name, $data = [])
        {
            foreach (self::$plugins as $plugin) {
                if (is_object($plugin) && method_exists($plugin, $name)) {
                    $data = $plugin->$name($data);
                }
            }
            return $data;
        }

        /**
         * 开启关闭插件使用
         * @param $plugin
         * @param bool $switcher
         * @return bool|string
         */
        public static function enablePlugin($plugin, $switcher = true)
        {
            try {
                $path = self::$pluginPath . "/$plugin/";
                $lock = $path . "/install.lock";
                $entry = "\\$plugin\\Action";
                if ($switcher) {
                    if (is_dir($path . "template")) {
                        self::rcopy($path, self::$rootPath . DS . "template/" . $plugin);
                    }
                    file_put_contents($lock, time());
                    return (new $entry)->__install();
                }
                unlink($lock);
                self::rrmdir(self::$rootPath . DS . "template/" . $plugin);
                return (new $entry)->__uninstall();
            } catch (\Exception $e) {
                return $e->getMessage();
            }
        }

        /**
         * 递归复制
         * @param $src
         * @param $dst
         */
        public function rcopy($src, $dst)
        {
            if (file_exists($dst)) $this->rrmdir($dst);
            if (is_dir($src)) {
                mkdir($dst);
                $files = scandir($src);
                foreach ($files as $file)
                    if ($file != "." && $file != "..") $this->rcopy("$src/$file", "$dst/$file");
            } else if (file_exists($src)) copy($src, $dst);
        }

        /**
         * 递归删除目录
         * @param $dir
         */
        public function rrmdir($dir)
        {
            if (is_dir($dir)) {
                $files = scandir($dir);
                foreach ($files as $file)
                    if ($file != "." && $file != "..") $this->rrmdir("$dir/$file");
                rmdir($dir);
            } else if (file_exists($dir)) unlink($dir);
        }
    }
}