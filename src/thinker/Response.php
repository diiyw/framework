<?php

namespace thinker {

    class Response
    {
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
        public function push($file, $dlName, $speed = 128)
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
         * 显示一个模板
         * @param string $action
         * @param array $var
         */
        public function view($action = "", $var = [])
        {
            if ($action == "") {
                $action = Container::load("response")->action;
            }
            Container::load("view")->display($action, $var);
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

