<?php

namespace thinker;

class Thinker
{
    public static function handle()
    {
        Registry::set("request", new Request());
        Registry::set("plugin", new Plugin(Registry::get("request")->projectPath . "/plugins"));
        Registry::get("plugin")->beforeDispatch();
        Registry::get("request", "parseUri");
        Registry::set("input", new Input());
        Registry::set("Response", new Response());
        Registry::set("view", new View());
        Registry::loadConfig("app");

        $class = Registry::get("request")->module . "\\" . ucfirst(Registry::get("request")->controller);
        $framer = new $class();
        $framer->{Registry::get("request")->action}();
    }
}