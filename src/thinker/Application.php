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
            $modulePath = $request->projectPath . "\modules";
            $includePath = get_include_path();
            $includePath .= PATH_SEPARATOR . $modulePath;
            $includePath .= PATH_SEPARATOR . $modulePath . DIRECTORY_SEPARATOR . $request->module . "/library";
            $includePath .= PATH_SEPARATOR . $modulePath . DIRECTORY_SEPARATOR . $request->module . "/model";
            set_include_path($includePath);
            // 自动加载
            spl_autoload_register(function ($class) use ($request) {
                $file = $class . ".php";
                @include_once $file;
            });
            Container::set("rule", include_once $request->module . "/config/rules.php");
            // 执行脚本
            $class = $request->module . "\\" . ucfirst($request->controller);
            new $class();
        }
    }
}