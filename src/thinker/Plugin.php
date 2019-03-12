<?php

namespace thinker {

    class Plugin extends Controller
    {

        /**
         * 插件目录
         * @var string
         */
        public $path;

        public function __construct()
        {
            parent::__construct();
            $this->path = App::$pluginPath . DS . App::$module;
            $this->view->setPath($this->path . DS . "views");
        }
    }
}