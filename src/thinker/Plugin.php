<?php

namespace thinker {

    class Plugin
    {
        private static $plugins = [];

        /**
         * 加载插件
         * @param $folder
         */
        public static function load($folder)
        {
            if (is_dir($folder)) {
                $handle = opendir($folder);
                while ($name = readdir($handle)) {
                    $file = $folder . DS . $pluginName;
                    if (is_file($file)) {
                        $pathInfo = pathinfo($file);
                        if ($pathInfo["extension"] == "php") {
                            include_once $file;
                            self::$plugins[] = "plugins\\" . $pathInfo["filename"];
                        }
                        continue;
                    }
                    if ($name != "." && $name != ".." && is_dir($file)) {
                        if (file_exists(PLUGIN_PATH . "/$plugin/install.lock")) {
                            $entry = "\\plugins\\" . $plugin . "\\Entry";
                            if (class_exists($entry)) {
                                self::$plugins[] = new $entry;
                            }
                        }
                    }
                }
            }
        }

        /**
         * 内置钩子
         */
        public static function beforeDispatch()
        {
            foreach (self::$plugins as $class) {
                $plugin = (new $class);
                if (method_exists($plugin, "beforeDispatch")) {
                    $plugin->beforeDispatch();
                }
            }
        }

        /**
         * 监听钩子
         * @param $name
         * @param $data
         * @return mixed
         */
        public static function hook($name, $data)
        {
            foreach (self::$plugins as $plugin) {
                if (is_object($plugin) && method_exists($listener, $name)) {
                    $data = $plugin->$name($data);
                }
            }
            return $data;
        }

        /**
         * 开启关闭插件使用
         * @param $plugin
         * @param bool $switcher
         * @return bool|int
         */
        public static function enable($plugin, $switcher = true)
        {
            $lock = App::$pluginPath . "/$plugin/install.lock";
            if ($switcher) {
                if (file_exists(App::$pluginPath . "/$plugin/Entry.php")) {
                    return file_put_contents($lock, time());
                }
                return false;
            }
            return @unlink($lock);
        }
    }
}


