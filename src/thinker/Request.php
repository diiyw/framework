<?php

namespace thinker;

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
        $module = "index",

        /**
         * 请求控制器
         * @var string
         */
        $controller = "index",

        /**
         * 请求动作
         * @var string
         */
        $action = "index",

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
        $projectPath,

        /**
         * 输入
         * @var Input
         */
        $input;

    /**
     * URl重写回调
     * @var callable
     */
    private $rewrite;


    public function __construct()
    {
        $this->userAgent = $_SERVER["HTTP_USER_AGENT"];
        $this->clientIP = $this->clientIPAddress();
        $this->method = $_SERVER["REQUEST_METHOD"];
        if (!empty($_SERVER["HTTP_REFERER"])) {
            $this->referer = $_SERVER["HTTP_REFERER"];
        }
        $this->input = new Input();
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
        $uri = $this->getUri();
        if (!empty($uri[1])) {
            $this->controller = $uri[1];
        }
        if (!empty($uri[2])) {
            $this->action = $uri[2];
        }
        if (count($uri) > 3) {
            $params = array_splice($uri, 3);
            //多余PATH_INFO解析为get请求参数
            foreach ($params as $k => $v) {
                if (isset($params[$k + 1])) {
                    $_REQUEST[$v] = $_GET[$v] = $params[$k + 1];
                    unset($params[$k + 1]);
                }
            }
        }
    }

    /**
     * 解析PATH_INFO
     * @return array
     */
    private function getUri()
    {
        $uri = trim(explode("?", $_SERVER["REQUEST_URI"])[0], "/");
        if (!empty($uri)) {
            $this->module = substr($uri, 0, strpos($uri, "/"));
            if (empty($this->module)) {
                $this->module = $uri;
            }
            if ($this->rewrite) {
                $this->pathInfo = $this->rewrite($this->pathInfo);
            }
            return explode("/", trim($this->pathInfo, "/"));
        }

        return isset($_SERVER["argv"]) ? array_splice($_SERVER["argv"], 1) : [];
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
     * 注册路由
     * @param callable $rewriter
     */
    public function registerRouter(callable $rewriter)
    {
        $this->rewrite = $rewriter;
    }
}