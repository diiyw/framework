<?php

namespace thinker;

class Framer
{
    /**
     * Request
     * @var Request
     */
    public $request;

    /**
     * Response
     * @var Response
     */
    public $response;

    /**
     * 表单
     * @var Form
     */
    public $form;

    /**
     * @var View
     */
    public $view;

    /**
     * @var Plugin
     */
    public $plugin;

    /**
     * 配置
     * @var array
     */
    public $config;

    /**
     * 注册对象
     * @param $name
     * @param $obj
     */
    public function register($name, $obj)
    {
        $this->$name = $obj;
    }

    /**
     * 加载配置
     * @param $name
     */
    public function loadConfig($name)
    {
        $this->config[$name] = include_once $this->request->app . "/config/" . $name . ".php";
    }
}