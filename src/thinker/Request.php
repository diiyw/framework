<?php

namespace thinker {

    class Request
    {
        public

            /**
             * 请求IP
             * @var string
             */
            $clientIP,

            /**
             * USER_AGENT
             * @var string
             */
            $userAgent,

            /**
             * PATH_INFO
             * @var string
             */
            $pathInfo,

            /**
             * 请求模块
             * @var string
             */
            $module,

            /**
             * 请求控制器
             * @var string
             */
            $controller,

            /**
             * 请求动作
             * @var string
             */
            $action,

            /**
             * 请求方法
             * @var string
             */
            $method,

            /**
             * 来源
             * @var string
             */
            $referer,

            /**
             * 请求时间
             * @var string
             */
            $requestTime,

            /**
             * ROOT目录
             * @var string
             */
            $rootPath,

            /**
             * 站点目录
             * @var string
             */
            $publicPath,

            /**
             * 应用目录
             * @var string
             */
            $projectPath;


        public function __construct()
        {
            $this->parseUri();
            $this->userAgent = $_SERVER["HTTP_USER_AGENT"];
            $this->clientIP = $this->clientIPAddress();
            $this->method = $_SERVER["REQUEST_METHOD"];
            if (!empty($_SERVER["HTTP_REFERER"])) {
                $this->referer = $_SERVER["HTTP_REFERER"];
            }
            $this->requestTime = $_SERVER["REQUEST_TIME"];
            $this->rootPath = dirname($_SERVER["DOCUMENT_ROOT"]);
            $this->publicPath = $_SERVER["DOCUMENT_ROOT"];
            $this->projectPath = $this->rootPath . "/app";
        }

        /**
         * IP转到整型
         * @return int
         */
        public function clientIp2long()
        {
            return ip2long($this->clientIP);
        }

        /**
         * 重定向
         * @param string $target
         * @return void
         */
        public function redirect($target = "/")
        {
            header("Location: " . $target);
        }

        /**
         * 解析PATH_INFO
         */
        public function parseUri()
        {
            $this->pathInfo = trim(explode("?", $_SERVER["REQUEST_URI"])[0], "/");
            $uri = explode("/", $this->pathInfo);
            $this->module = array_shift($uri);
            if (!$this->module) {
                $this->module = "home";
            }
            $this->controller = array_shift($uri);
            if (!$this->controller) {
                $this->controller = "Index";
            }
            $this->action = array_shift($uri);
            if (!$this->action) {
                $this->action = "index";
            }
            //多余PATH_INFO解析为get请求参数
            foreach ($uri as $k => $v) {
                if (isset($uri[$k + 1])) {
                    $_REQUEST[$v] = $_GET[$v] = $uri[$k + 1];
                    unset($uri[$k + 1]);
                }
            }
        }

        /**
         * 获取客户端IP地址
         * @return string IP
         */
        function clientIPAddress()
        {
            $ip = "0.0.0.0";
            if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
                $ip = getenv('HTTP_CLIENT_IP');
            } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
                $ip = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
                $ip = getenv('REMOTE_ADDR');
            } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }

            return $ip;
        }

        /**
         * 是否是post请求
         * @return bool
         */
        public function isPost()
        {
            return $_SERVER["REQUEST_METHOD"] == "POST";
        }

        /**
         * 是否是get请求
         * @return bool
         */
        public function isGet()
        {
            return $_SERVER["REQUEST_METHOD"] == "GET";
        }

        /**
         * 是否是put请求
         * @return bool
         */
        public function isPut()
        {
            return $_SERVER["REQUEST_METHOD"] == "PUT";
        }

        /**
         * 是否是delete请求
         * @return bool
         */
        public function isDelete()
        {
            return $_SERVER["REQUEST_METHOD"] == "DELETE";
        }

        /**
         * 是否是ajax请求
         * @return bool
         */
        public function isAjax()
        {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                return true;
            }
            return false;
        }

        /**
         * $_GET
         * @param string $name
         * @param string $default
         * @return array|string
         */
        public function get($name = "", $default = "")
        {
            if (!$name) {
                return $this->removeXSS($_GET);
            }
            if (isset($_GET[$name])) {
                return $this->removeXSS($_GET[$name]);
            }
            return $default;
        }

        /**
         * $_POST
         * @param string $name
         * @param string $default
         * @return array|string
         */
        public function post($name = "", $default = "")
        {
            if (!$name) {
                return $this->removeXSS($_POST);
            }
            if (isset($_POST[$name])) {
                return $this->removeXSS($_POST[$name]);
            }
            return $default;
        }

        /**
         * $_INPUT
         * @return array|string
         */
        public function input()
        {
            $input = file_get_contents('php://input');
            $array = json_decode($input, true);
            if ($array) {
                return $this->removeXSS($array);
            }
            return $input;
        }

        /**
         * $_REQUEST
         * @param $name
         * @param string $default
         * @return array|string
         */
        public function data($name = "", $default = "")
        {
            if (!$name) {
                return $this->removeXSS($_REQUEST);
            }
            if (isset($_REQUEST[$name])) {
                return $this->removeXSS($_REQUEST[$name]);
            }
            return $default;
        }

        /**
         * XSS过滤
         * @param $data
         * @return array|string
         */
        private function removeXSS($data)
        {
            if (!is_array($data)) {
                if (mb_detect_encoding($data, "UTF-8")) {
                    return htmlspecialchars(trim($data));
                }

                return htmlspecialchars(trim($data), ENT_QUOTES, 'GB2312');
            }
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    $this->removeXSS($v);
                } else {
                    $data[$k] = htmlspecialchars(trim($v));
                }
            }
            return $data;
        }
    }
}

