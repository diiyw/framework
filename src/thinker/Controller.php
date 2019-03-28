<?php

namespace thinker {

    class Controller
    {
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
        public $formData;

        /**
         * Controller constructor.
         */
        public function __construct()
        {
            // 初始化
            $this->http = new Http();
            $this->view = new View();
        }

        //执行
        public function resolve()
        {
            // 表单过滤
            $filter = static::class . "Filter";
            try {
                if ($this->http->isAjax()) {
                    $resp = [];
                    header("Content-Type:application/json;charset:utf-8");
                    switch ($this->http->method) {
                        case "CLI_GET":
                        case "GET":
                            $filter = new $filter($this->http->get(), "get");
                            if ($filter->error()) {
                                $this->error(1000, $filter->error());
                            }
                            $this->formData = $filter->data;
                            $resp = $this->get();
                            break;
                        case "POST":
                            $filter = new $filter($this->http->post(), "post");
                            if ($filter->error()) {
                                $this->error(1000, $filter->error());
                            }
                            $this->formData = $filter->data;
                            $resp = $this->post();
                            break;
                        case "PUT":
                            $filter = new $filter($this->http->input(), "put");
                            if ($filter->error()) {
                                $this->error(1000, $filter->error());
                            }
                            $this->formData = $filter->data;
                            $resp = $this->put();
                            break;
                        case "DELETE":
                            $filter = new $filter($this->http->input(), "delete");
                            if ($filter->error()) {
                                $this->error(1000, $filter->error());
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
                $filter = new $filter($this->http->get(), "view");
                if ($filter->error()) {
                    throw new \Exception($filter->error(), 1000);
                }
                $this->formData = $filter->data;
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
        public function success($json, $code = 200)
        {
            $data = array(
                "code" => $code,
            );
            if (is_string($json)) {
                $data["message"] = $json;
            } else {
                $data["result"] = $json;
            }
            $this->http->json($data);
        }

        /**
         * 表单错误处理，可以被重写自动定义处理方式
         * @param \Exception $exception
         * @throws \Exception
         */
        public function _ajaxException(\Exception $exception)
        {
            if ($exception->getCode() > 1000) {
                $this->error($exception->getCode(), $exception->getMessage());
            }
            $this->error(500, $exception->getMessage());
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

