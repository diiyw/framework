<?php

namespace thinker {

    class Controller
    {
        protected $errorCode = 0;

        protected $message = "Invalid request";

        /**
         * Controller constructor.
         */
        public function __construct()
        {
            $request = Container::load("request");
            $response = Container::load("response");
            $this->{$request->action}($request, $response);
            try {
                if ($request->isAjax()) {
                    $resp = [];
                    $action = ucfirst($request->action);
                    header("Content-Type:application/json;charset:utf-8");
                    switch ($_SERVER["REQUEST_METHOD"]) {
                        case "GET":
                            $resp = $this->{"get" . $action}($request, $response);
                            break;
                        case "POST":
                            $resp = $this->{"post" . $action}($request, $response);
                            break;
                        case "PUT":
                            $resp = $this->{"put" . $action}($request, $response);
                            break;
                        case "DELETE":
                            $resp = $this->{"delete" . $action}($request, $response);
                            break;
                        default:
                            $this->errorCode = 500;
                            $this->message = "Unsupported method";
                    }
                    if ($this->errorCode != 0) {
                        $this->error($this->errorCode, $this->message);
                    }
                    $this->success($resp);
                }
                $this->{$request->action}($request);
            } catch (\Exception $e) {
                if ($request->isAjax()) {
                    $this->_AjaxException($e);
                }
                $this->_ThinkerException($e);
            }
        }

        /**
         * 加载容器中的对象
         * @param $obj
         * @return Input|Plugin|Request|Response|View
         */
        public function load($obj)
        {
            return Container::load($obj);
        }

        /**
         * 模板显示
         * @param $tpl
         * @param array $var
         */
        public function display($tpl, $var = [])
        {
            $this->load("view")->display($tpl, $var);
        }

        /**
         * 错误json输出
         * @param $code
         * @param $message
         */
        public function error($code, $message)
        {
            echo json_encode([
                "code" => $code,
                "message" => $message
            ]);
            die(0);
        }

        /**
         * 成功json输出
         * @param $data
         */
        public function success($data)
        {
            echo json_encode([
                "code" => 200,
                "result" => $data
            ]);
            die(0);
        }

        /**
         * 表单错误处理，可以被重写自动定义处理方式
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
            echo $exception->getMessage();
        }
    }
}

