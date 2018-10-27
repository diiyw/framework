<?php

namespace thinker {
    class Thinker
    {
        public static function handle()
        {
            $request = DI::set("request", new Request());
            DI::set("plugin", new Plugin($request->projectPath . "/plugins"));
            DI::load("plugin")->beforeDispatch();
            DI::load("request", "parseUri");
            DI::set("form", new Form());
            DI::set("response", new Response());
            DI::set("view", new View());
            DI::loadConfig("app");
            // Ö´ÐÐ½Å±¾
            $class = $request->module . "\\" . ucfirst($request->controller);
            $framer = new $class();
        }
    }
}

