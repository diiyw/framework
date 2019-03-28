<?php

namespace thinker {

    class Errors
    {
        private static $err = array();

        static function file($message, $dest = '')
        {
            if (empty($dest)) {
                $dest = App::$publicPath . '/logs/app' . date('Y_m_d') . '.log';
            }
            $log_dir = dirname($dest);
            if (!is_dir($log_dir)) {
                mkdir($log_dir, 0755, true);
            }
            if (is_file($dest) && 2048000 <= filesize($dest)) {
                rename($dest, dirname($dest) . '/' . time() . '-' . basename($dest));
            }
            $ip = empty($_SERVER["REMOTE_ADDR"]) ? "" : $_SERVER["REMOTE_ADDR"];
            $uri = empty($_SERVER["REQUEST_URI"]) ? "" : $_SERVER["REQUEST_URI"];
            error_log("[" . date("Y-m-d H:i:s") . "] " . $ip . $uri . "\r\n {$message}\r\n", 3, $dest);
            return false;
        }

        static function json($message)
        {
            header("Content-Type:application/json;charset:utf-8");
            exit(json_encode(array(
                "message" => $message,
                "code" => 100,
                "flag" => false,
            )));
        }

        static function panic($message, Exception $previous = null)
        {
            throw new \Exception($message, 100, $previous);
        }

        static function set($message, $code = 100)
        {
            self::$err[] = $code;
            self::$err[] = $message;
            return false;
        }

        static function getMessage()
        {
            if (empty(self::$err[1])) {
                return "";
            }
            return self::$err[1];
        }

        static function getCode()
        {
            if (empty(self::$err[0])) {
                return "";
            }
            return self::$err[0];
        }
    }
}

