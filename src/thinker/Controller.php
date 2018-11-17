<?php

namespace thinker {

    class Controller
    {
        protected $errorCode = 0;

        protected $message = "Invalid request";

        protected $rules = [];

        /**
         * @var Request
         */
        protected $request;


        /**
         * @var Response
         */
        protected $respnose;

        /**
         * Controller constructor.
         */
        public function __construct()
        {
            $this->request = Container::load("request");
            $this->response = Container::load("response");
            $this->rules = Container::loadConfig("rules");
            try {
                if ($this->request->isAjax()) {
                    $resp = [];
                    $action = ucfirst($this->request->action);
                    header("Content-Type:application/json;charset:utf-8");
                    switch ($_SERVER["REQUEST_METHOD"]) {
                        case "GET":
                            $resp = $this->{"get" . $action}();
                            break;
                        case "POST":
                            $resp = $this->{"post" . $action}();
                            break;
                        case "PUT":
                            $resp = $this->{"put" . $action}();
                            break;
                        case "DELETE":
                            $resp = $this->{"delete" . $action}();
                            $this->errorCode = 500;
                            break;
                        default:
                            $this->message = "Unsupported method";
                    }
                    if ($this->errorCode != 0) {
                        $this->error($this->errorCode, $this->message);
                    }
                    $this->success($resp);
                }
                $this->{$this->request->action}();
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    $this->_AjaxException($e);
                }
                $this->_ThinkerException($e);
            }
        }

        /**
         * 加载容器中的对象
         * @param $obj
         * @return Request|Response|View
         */
        public function load($obj)
        {
            return Container::load($obj);
        }

        /**
         * 错误json输出
         * @param $code
         * @param $message
         */
        public function error($code, $message)
        {
            Container::load("response")->json(array(
                "code" => $code,
                "message" => $message,
            ));
        }

        public function filter(Request $request, $rule)
        {
            if (!isset($this->rules[$rule])) {
                return $request->data();
            }
            $filter = new Filter($request, $this->rules[$rule]);
            return $filter->data();
        }

        /**
         * 成功json输出
         * @param $data
         */
        public function success($data)
        {
            Container::load("response")->json(array(
                "code" => 200,
                "result" => $data,
            ));
        }

        /**
         * 表单错误处理，可以被重写自动定义处理方式
         * @param \Exception $exception
         */
        public function _AjaxException(\Exception $exception)
        {
            if ($exception->getCode() > 10000) {
                $this->error($exception->getCode(), $exception->getMessage());
            }
            $this->error(500, "error occupied");
        }

        /**
         * 业务错误处理，可以被重写自动定义处理方式
         */
        public function _ThinkerException(\Exception $exception)
        {
            throw $exception;
        }
    }
}

