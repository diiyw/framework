<?php

namespace thinker {

    class Application
    {
        /**
         * Application constructor.
         */
        public function __construct()
        {
            // 初始化
            $request = Container::set("request", new Request());
            Container::set("response", new Response());
            Container::set("view", new View());
            // 启动插件
            $plugin = Container::set("plugin", new Plugin());
            $plugin->load($request->projectPath . "/plugins");
            $plugin->beforeDispatch();
            // 自动加载
            spl_autoload_register(function ($class) use ($request) {
                $file = $request->projectPath . "/modules/" . $class . ".php";
                if (file_exists($file)) {
                    include_once $file;
                }
            });
            // 执行脚本
            $class = $request->module . "\\" . ucfirst($request->controller);
            new $class();
        }
    }
}