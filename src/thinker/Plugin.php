<?php

namespace thinker {

    class Plugin
    {

        private $plugins = [];

        /**
         * ¼ÓÔØ²å¼þ
         * Plugin constructor.
         * @param Framer $framer
         */
        public function __construct($pluginsPath)
        {
            $this->loadPlugins($pluginsPath);
        }

        private function loadPlugins($folder)
        {
            if (is_dir($folder)) {
                $handle = opendir($folder);
                while ($file = readdir($handle)) {
                    $file = $folder . '/' . $file;
                    if (is_file($file)) {
                        $pathInfo = pathinfo($file);
                        if ($pathInfo["extension"] == "php") {
                            include_once $file;
                            $this->plugins[] = "plugins\\" . $pathInfo["filename"];
                        }
                    }
                }
            }
        }

        public function beforeDispatch()
        {
            foreach ($this->plugins as $class) {
                $plugin = (new $class);
                if (method_exists($plugin, "beforeDispatch")) {
                    $plugin->beforeDispatch();
                }
            }
        }
    }
}


