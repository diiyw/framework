<?php

namespace thinker {

    class Http
    {
        /**
         * 请求IP
         * @var string
         */
        public $clientIP;

        /**
         * USER_AGENT
         * @var string
         */
        public $userAgent;

        /**
         * 请求方法
         * @var string
         */
        public $method;

        /**
         * 来源
         * @var string
         */
        public $referer;

        /**
         * 请求时间
         * @var string
         */
        public $requestTime;


        public function __construct()
        {
            $this->userAgent = $_SERVER["HTTP_USER_AGENT"] ?? "PHP_CLI";
            $this->clientIP = $this->clientIPAddress();
            $this->method = $_SERVER["REQUEST_METHOD"] ?? "CLI_GET";
            if (!empty($_SERVER["HTTP_REFERER"])) {
                $this->referer = $_SERVER["HTTP_REFERER"];
            }
            $this->requestTime = $_SERVER["REQUEST_TIME"];
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

        public function setCookie($key, $value = null, $expire = null, $path = null, $domain = null, $secure = null, $httponly = null)
        {
            setcookie($key, $value, $expire, $path, $domain, $secure, $httponly);
        }

        public function getCookie($key)
        {
            return isset($_COOKIE[$key]) ? $_COOKIE[$key] : null;
        }

        public function removeCookie($key)
        {
            unset($_COOKIE[$key]);
        }

        public function destroyCookie()
        {
            unset($_COOKIE);
        }

        public function setSession($key, $value = null)
        {
            $_SESSION[$key] = $value;
            return true;
        }

        public function sessionStart()
        {
            if (session_status() != 2) {
                session_start();
            }
        }

        public function removeSession($key)
        {
            unset($_SESSION[$key]);
        }

        public function getSession($key)
        {
            return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
        }

        public function destroySession()
        {
            session_destroy();
        }

        /**
         * 设置响应HTTP代码
         * @param $code
         */
        public function code($code)
        {
            http_response_code($code);
        }

        /**
         * 推送一个文件
         * @param $file
         * @param $dlName
         * @param int $speed
         */
        public function send($file, $dlName, $speed = 128)
        {
            if (file_exists($file) && is_file($file)) {
                header('Cache-control: private');
                header('Content-Type: application/octet-stream');
                header("Accept-Ranges:bytes");
                header('Content-Length: ' . filesize($file));
                header('Content-Disposition: attachment; filename=' . $dlName);
                header('Content-Transfer-Encoding: binary');
                flush();
                $fh = fopen($file, "r");
                while (!feof($fh)) {
                    echo fread($fh, round($speed * 1024));
                    flush();
                    sleep(1);
                }
                fclose($fh);
            }
        }

        /**
         * 输出JSON格式数据
         * @param array $data
         */
        public function json(array $data)
        {
            header("Content-Type:application/json;charset:utf-8");
            echo json_encode($data);
            exit(0);
        }

        /**
         * 抛出错误
         * @param $error
         * @throws \Exception
         */
        public function error($error)
        {
            throw new \Exception($error);
        }
    }
}