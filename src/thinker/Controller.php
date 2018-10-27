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
            $request = DI::load("request");
            $this->{$request->action}($request);

            $request = DI::load("request");
            if ($request->isAjax()) {
                try {
                    $action = ucfirst($request->action);
                    header("Content-Type:application/json;charset:utf-8");
                    switch ($_SERVER["REQUEST_METHOD"]) {
                        case "GET":
                            $response = $this->{"get" . $action}($request);
                        case "POST":
                            $response = $this->{"post" . $action}($request);
                        case "PUT":
                            $response = $this->{"put" . $action}($request);
                        case "DELETE":
                            $response = $this->{"delete" . $action}($request);
                    }
                    if ($this->errorCode != 0) {
                        $this->error($this->errorCode, $this->message);
                    }
                    $this->success($response);
                } catch (\Exception $e) {
                    $this->_except($e);
                }
            }
            $this->{$request->action}($request);
        }

        /**
         * ���������еĶ���
         * @param $obj
         * @return Input|Plugin|Request|Response|View
         */
        public function load($obj)
        {
            return DI::load($obj);
        }

        /**
         * ģ����ʾ
         * @param $tpl
         * @param array $var
         */
        public function display($tpl, $var = [])
        {
            $this->load("view")->display($tpl, $var);
        }

        /**
         * ����json���
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
         * �ɹ�json���
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
         * �����������Ա���д�Զ����崦����ʽ
         */
        public function _AjaxException(\Exception $exception)
        {
            $this->error($e->getCode(), $e->getMessage());
        }

        /**
         * �����������Ա���д�Զ����崦����ʽ
         */
        public function _Exception(\Exception $exception)
        {
            echo $exception->getMessage();
        }
    }
}
