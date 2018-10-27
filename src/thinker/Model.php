<?php

namespace thinker {
    class Model
    {

        /**
         * Where 条件
         * @var array
         */
        private $_where = [];

        /**
         * 排序条件
         * @var array
         */
        private $_orderBy = [];

        /**
         * 分组条件
         * @var array
         */
        private $_groupBy = [];

        /**
         * 限制条件
         * @var array
         */
        private $_limit = 1;

        /**
         * 分页
         * @var array
         */
        private $_page = 0;

        /**
         * 数据绑定
         * @var array
         */
        private $_binds = [];

        /**
         * 数据表
         * @var string
         */
        protected $_table;

        /**
         * PDO连接对象
         * @var \PDO
         */
        protected $_conn;

        /**
         * 默认连接名称
         * @var string
         */
        protected $_name = "default";

        /**
         * 主键名称
         * @var string
         */
        protected $_primaryKey;

        /**
         * 新建模型
         * @param $model
         */
        public function __construct($mapper = [])
        {
            $objName = "CONN::" . $this->name;
            $config = DI::load("dbConfig")[$this->name];
            if (!DI::load($objName) instanceof \PDO) {
                DI::set($objName, new \PDO(
                    $config['dsn'], $config['user'], $config['password'],
                    [\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']
                ));
            }
            $this->_conn = DI::load($objName);
        }

        /**
         * 条件
         * @param array $where
         */
        public function where(array $where)
        {
            if (empty($where)) {
                return;
            }
            $this->where = array_merge($this->where, $where);
            return $this;
        }

        /**
         * 根据主键查询最新一条记录
         * @param array $where
         */
        public function last($primaryKey = "")
        {
            if (empty($primaryKey)) {
                $this->first();
                return;
            }
            $this->where([
                $this->_primaryKey => ["=", $primaryKey]
            ]);
            return $this->select("*");
        }

        /**
         * 限制查询
         * @param $start
         * @param int $number
         */
        public function limit($limit)
        {
            $this->limit = $limit;
            return $this;
        }

        /**
         * 分页
         * @param $page
         * @param int $number
         */
        public function page($page)
        {
            $this->page = $page;
            return $this;
        }

        /**
         * 分组
         * @param $by
         */
        public function groupBy($by)
        {
            $this->groupBy = $by;
            return $this;
        }

        /**
         * 排序
         * @param $by
         */
        public function orderBy(array $by)
        {
            if (empty($by)) {
                return;
            }
            $this->orderBy = array_merge($this->orderBy, $by);
            return $this;
        }

        private function buildSql()
        {
            $where = "";
            foreach ($this->_where as $field => $item) {
                $bound = " AND ";
                $this->binds[] = $item[1];
                if (isset($item[2])) {
                    $bound = $item[2];
                }
                $where .= $bound . $field . $item[0] . "?";
            }
            if (!empty($where)) {
                $where = " WHERE " . ltrim($where, "AND");
            }
            $orderBy = "";
            foreach ($this->_orderBy as $item) {
                $orderBy .= join(" ", $item) . ",";
            }
            if (!empty($orderBy)) {
                $orderBy = " ORDER BY " . $orderBy;
            }
            $groupBy = "";
            foreach ($this->_groupBy as $item) {
                $groupBy .= join(",", $item);
            }
            if (!empty($groupBy)) {
                $groupBy = " GROUP BY " . $orderBy;
            }
            $limit = "";
            if (!empty($this->_limit)) {
                $limit = intval($this->_limit);
            }
            if (!empty($this->_page)) {
                $limit = intval($this->_page * $limit) . "," . intval($limit);
            }
            return $where . " " . $orderBy . " " . $groupBy . " LIMIT " . $limit;
        }


        /**
         * 插入数据
         * @return bool|integer
         */
        public function insert()
        {
            $data = $this->toArray();
            if (empty($data)) {
                return false;
            }
            $fields = array_keys($data);
            $this->_binds = array_values($data);
            $bind = join(",:", $fields);
            $this->query("INSERT INTO " . $this->getTableName() . "(" . join(",", $fields) . ")VALUES(" . $bind . ")");
            return $this->_conn->lastInsertId();
        }

        /**
         * 更新数据
         * @return int
         * @throws \Exception
         */
        public function update()
        {
            $mapper = $this->toArray();
            if (empty($mapper)) {
                return 0;
            }
            $sets = "";
            $binds = [];
            foreach ($mapper as $field => $value) {
                $binds[] = $value;
                $sets .= $field . "=?,";
            }
            $sets = trim($sets, ",");
            $sql = "UPDATE " . $this->getTableName() . " SET " . $sets . " WHERE " . $this->buildSql();
            $this->_binds = array_merge($binds, $this->_binds);
            $result = $this->query($sql);
            return $result->rowCount();
        }

        /**
         * 删除数据
         * @return int
         */
        public function delete()
        {
            $sql = "DELETE FROM " . $this->getTableName() . $this->buildSql();
            $result = $this->query($sql);
            return $result->rowCount();
        }

        /**
         * 数据查询
         * @param $colunms
         * @param bool $all
         * @return array|mixed
         * @throws \Exception
         */
        public function select($colunms = "*")
        {
            $sql = "SELECT " . $colunms . " FROM " . $this->getTableName() . $this->buildSql();
            $result = $this->query($sql);
            return $result->fetchAll();
        }

        public function first()
        {
            $sql = "SELECT " . $this->_colunms . " FROM " . $this->getTableName() . $this->buildSql();
            $result = $this->query($sql);
            return $result->fetch();
        }

        /**
         * 执行SQl语句
         * @param $sql
         * @param array $data
         * @return \PDOStatement
         * @throws \Exception
         */
        public function query($sql)
        {
            $this->_sql = $sql;
            $result = $this->_conn->prepare($sql);
            foreach ($this->_binds as $key => &$value) {
                if (trim($value) == "") {
                    continue;
                }
                $result->bindParam($key, $value, is_numeric($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
            }
            $result->setFetchMode(\PDO::FETCH_ASSOC);
            $result->execute();
            $errorInfo = $result->errorInfo();
            if ($errorInfo[2]) {
                throw new \Exception($errorInfo[2], $errorInfo[1]);
            }
            return $result;
        }

        /**
         * 事务处理
         * @param callable $tx
         * @return bool
         */
        public function tx(callable $tx)
        {
            $this->_conn->beginTransaction();
            $result = $tx();
            if ($result) {
                $this->_conn->commit();
                return $result;
            }
            $this->_conn->rollBack();
            return false;
        }

        /**
         * Pdo查询结果转换到对象操作
         * @param $name
         * @param $value
         */
        public function __set($name, $value)
        {
            $this->{$this->convertUnderline($name)} = $value;
        }

        /**
         * 驼峰命名
         * @param $str
         * @return null|string|string[]
         */
        protected function convertUnderline($str)
        {
            $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
                return strtoupper($matches[2]);
            }, $str);
            return $str;
        }

        /**
         * 执行后的sql语句
         * @return mixed
         */
        public function sql()
        {
            return $this->_sql;
        }

        /**
         * 获取表名（重写可做分表操作）
         * @return string
         */
        public function getTableName()
        {
            return $this->_table;
        }
    }
}

