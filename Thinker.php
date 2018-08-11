<?php

namespace thinker;

class Thinker
{
    public static function launch()
    {
        define("THINKER_VER", "0.1");

        $request = new Request();

        $plugin = new Plugin($request);

        $controller = $request->module . "\\" . ucfirst($request->controller);

        $framer = new $controller;

        $framer->register("request", $request);

        $framer->register("plugin", $plugin);

        $framer->register("form", $request->form);

        $framer->register("response", new Response());

        $framer->register("view", new View($request));

        $framer->{$request->action}();
    }
}