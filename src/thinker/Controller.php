<?php

namespace thinker {

    class Controller
    {
        /**
         * @var Plugin
         */
        protected $plugin;

        /**
         * @var Http
         */
        protected $http;

        /**
         * @var View
         */
        protected $view;

        public $errorCode = 200;

        public $message = "success";

        /**
         * 表单数据
         * @var array
         */
        protected $formData;

        /**
         * Controller constructor.
         */
        public function __construct()
        {
            // 初始化
            $this->http = new Http();
            $this->plugin = new Plugin();
            $this->view = new View();
            // 启动插件
            $this->plugin->load(App::$projectPath . DS . "plugins");
            $this->plugin->beforeDispatch();
            $filter = App::$module . "\\" . ucfirst(App::$controller) . "Filter";
            try {
                if ($this->http->isAjax()) {
                    $resp = [];
                    header("Content-Type:application/json;charset:utf-8");
                    switch ($_SERVER["REQUEST_METHOD"]) {
                        case "GET":
                            $filter = new $filter($this->http->get());
                            if ($error = $filter->error() && !empty($error)) {
                                $this->error(1000, $error);
                            }
                            $this->formData = $filter->data;
                            $resp = $this->get();
                            break;
                        case "POST":
                            $filter = new $filter($this->http->post());
                            if ($error = $filter->error() && !empty($error)) {
                                $this->error(1000, $error);
                            }
                            $this->formData = $filter->data;
                            $resp = $this->post();
                            break;
                        case "PUT":
                            $filter = new $filter($this->http->put());
                            if ($error = $filter->error() && !empty($error)) {
                                $this->error(1000, $error);
                            }
                            $this->formData = $filter->data;
                            $resp = $this->put();
                            break;
                        case "DELETE":
                            $filter = new $filter($this->http->delete());
                            if ($error = $filter->error() && !empty($error)) {
                                $this->error(1000, $error);
                            }
                            $this->formData = $filter->data;
                            $resp = $this->delete();
                            break;
                        default:
                            $this->errorCode = 404;
                            $this->message = "未知错误";
                    }
                    if ($this->errorCode != 200) {
                        $this->error($this->errorCode, $this->message);
                    }
                    $this->success($resp);
                }
                $this->view();
            } catch (\Exception $e) {
                if ($this->http->isAjax()) {
                    $this->_ajaxException($e);
                }
                $this->_thinkerException($e);
            }
        }

        /**
         * 错误json输出
         * @param $code
         * @param $message
         * @throws \Exception
         */
        public function error($code, $message)
        {
            if ($this->http->isAjax()) {
                $this->http->json(array(
                    "code" => $code,
                    "message" => $message,
                ));
            }
            throw new \Exception($message, $code);
        }

        /**
         * 成功json输出
         * @param $data
         */
        public function success($data)
        {
            $this->http->json(array(
                "code" => 200,
                "result" => $data,
            ));
        }

        /**
         * 表单错误处理，可以被重写自动定义处理方式
         * @param \Exception $exception
         * @throws \Exception
         */
        public function _ajaxException(\Exception $exception)
        {
            if ($exception->getCode() > 10000) {
                $this->error($exception->getCode(), $exception->getMessage());
            }
            $this->error(500, "error occupied");
        }

        /**
         * 业务错误处理，可以被重写自动定义处理方式
         * @param \Exception $exception
         * @throws \Exception
         */
        public function _thinkerException(\Exception $exception)
        {
            throw $exception;
        }
    }
}

