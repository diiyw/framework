<?php

namespace thinker {

    use Medoo\Medoo;

    class Model
    {
        private $_name;

        /**
         * @var Medoo
         */
        private $_conn;

        /**
         * 新建模型
         * @param string $name
         * @throws \Exception
         */
        public function __construct($name = "")
        {
            if (!empty($name)) {
                $this->_name = $name;
            }
            $objName = "CONN::" . $this->_name;
            $config = Container::load("dbConfig")[$this->_name];
            try {
                if (!Container::load($objName) instanceof \PDO) {
                    Container::set($objName, new Medoo(
                        [
                            'database_type' => 'mysql',
                            'database_name' => $config["db_name"],
                            'server' => $config["host"],
                            'username' => $config["username"],
                            'password' => $config["password"],

                            'charset' => 'utf8',
                            'port' => 3306,
                        ]
                    ));
                }
                $this->_conn = Container::load($objName);
            } catch (Exception $e) {
                $message = $e->getMessage();
                if (empty($message)) {
                    $message = "Connect database failed";
                }
                throw new \Exception($message, $e->getCode());
            }
        }
    }
}