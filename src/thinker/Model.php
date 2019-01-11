<?php

namespace thinker {

    use  Medoo\Medoo;git

    class Model extends Medoo
    {
        private $_name;

        private $_sql = [];

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
            $config = App::load("dbConfig")[$this->_name];
            try {
                try {
                    if (!App::load($objName) instanceof \PDO) {
                        App::set($objName, new \PDO(
                            $config['dsn'], $config['user'], $config['password'],
                            [\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']
                        ));
                    }
                    parent::__contruct([
                        "pdo" => App::load($objName),
                        'database_type' => 'mysql',
                    ]);
                } catch (\PDOException $e) {
                    $message = $e->getMessage();
                    if (empty($message)) {
                        $message = "Connect database failed";
                    }
                    throw new \Exception($message, $e->getCode());
                }
            } catch (Exception $e) {
                $message = $e->getMessage();
                if (empty($message)) {
                    $message = "Connect database failed";
                }
                throw new \Exception($message, $e->getCode());
            }
        }

        /**
         * Where条件拼装
         * @param $conditions
         */
        public function where($conditions)
        {
            $this->_sql = array_merge($this->_sql, $conditions);
        }

        /**
         * 返回Select结果
         */
        public function result($colunms = "*")
        {
            return $this->select($this->table, $colunms, $this->_sql);
        }

        /**
         * 获取数据库最新的记录
         */
        public function getLast($colunms = "*", $limit = 1, $page = 0)
        {
            return $this->select($this->table, $colunms, [
                'LIMIT' => [$page, $limit],
            ]);
        }
    }
}