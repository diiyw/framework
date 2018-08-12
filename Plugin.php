<?php

namespace thinker;

class Plugin
{

    public $plugins = [];

    /**
     * ¼ÓÔØ²å¼þ
     * Plugin constructor.
     * @param Framer $framer
     */
    public function __construct(Framer $framer)
    {
        $this->loadPlugins($framer->request->app . "/plugins", $framer);
    }

    private function loadPlugins($folder, Framer $framer)
    {
        if (is_dir($folder)) {
            $handle = opendir($folder);
            while ($file = readdir($handle)) {
                $file = $folder . '/' . $file;
                if (is_file($file)) {
                    $pathInfo = pathinfo($file);
                    if ($pathInfo["extension"] == "php") {
                        include_once $file;
                        $this->plugins[] = $pathInfo["filename"];
                        (new $pathInfo["filename"])->beforeDispatch($framer);
                    }
                }
            }
        }
    }
}
