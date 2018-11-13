<?php

namespace thinker {

    class Application
    {
        public function __construct()
        {
            $request = Container::set("request", new Request());
            Container::set("response", new Response());
            Container::set("view", new View());
            Container::loadConfig("app");
            // 执行脚本
            $class = $request->module . "\\" . ucfirst($request->controller);
            new $class();
        }
    }
}