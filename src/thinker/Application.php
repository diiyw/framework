<?php

namespace thinker {

    class Application
    {
        /**
         * Application constructor.
         */
        public function __construct()
        {
            $request = Container::set("request", new Request());
            Container::set("response", new Response());
            Container::set("view", new View());
            $plugin = Container::set("plugin", new Plugin());
            $plugin->load($request->projectPath . "/plugins");
            // 执行脚本
            $class = $request->module . "\\" . ucfirst($request->controller);
            new $class();
        }
    }
}