<?php

namespace thinker;

class Plugin
{

    public $plugins = [];

    /**
     * ¼ÓÔØ²å¼þ
     * Plugin constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->loadPlugins($request->root . "/app/plugins", $request);
    }

    private function loadPlugins($folder, Request $request)
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
                        (new $pathInfo["filename"])->beforeDispatch($request);
                    }
                }
            }
        }
    }
}
