<?php

namespace thinker;

class Framer
{
    /**
     * Request
     * @var Request
     */
    protected $request;

    /**
     * Response
     * @var Response
     */
    protected $response;

    /**
     * 表单
     * @var Form
     */
    protected $form;

    /**
     * @var View
     */
    protected $view;

    /**
     * @var Plugin
     */
    protected $plugin;

    /**
     * 注册对象
     * @param $name
     * @param $obj
     */
    public function register($name, $obj)
    {
        $this->{$name} = $obj;
    }
}