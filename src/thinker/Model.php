<?php

namespace thinker {

    use  Medoo\Medoo;

    class Model extends Medoo
    {
        protected $__name = "default";

        private $_where = [];

        /**
         * 新建模型
         * @param string $name
         * @throws \Exception
         */
        public function __construct($options = [])
        {
            $this->table = $this->getTableName();
            $ns = explode("\\", static::class);
            if (empty($options)) {
                $module = array_shift($ns);
                $moduleConst = $module . "\\" . ucfirst($module) . "Const";
                $options = null !== $moduleConst::DB_CONFIG ? $moduleConst::DB_CONFIG[$this->__name] : [];
            }
            $objName = "CONN::" . $this->__name;
            try {
                try {
                    if (!App::load($objName) instanceof \PDO) {
                        App::set($objName, new \PDO(
                            $options['dsn'], $options['user'], $options['password'],
                            [\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']
                        ));
                    }
                    parent::__construct([
                        "pdo" => App::load($objName),
                        'database_type' => 'mysql',
                    ]);
                } catch (\PDOException $e) {
                    $message = $e->getMessage();
                    if (empty($message)) {
                        $message = "Connect database failed";
                    }
                    throw new \Exception("PDO:" . $message, $e->getCode());
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
            $this->_where = array_merge($this->getWhere(), $conditions);
            return $this;
        }

        /**
         * 返回Select结果
         */
        public function result($colunms = "*")
        {
            return $this->select($this->table, $colunms, $this->getWhere());
        }

        /**
         * 获取数据库最新的记录
         */
        public function getLast($join = null, $colunms = null, $page = 0, $limit = 10)
        {
            $this->_where["LIMIT"] = [($page - 1) * $limit, $limit];
            if ($join) {
                return $this->select($this->table, $join, $colunms, $this->getWhere());
            }
            return $this->select($this->table, $colunms, $this->getWhere());
        }

        /**
         * 插入一条记录
         */
        public function create()
        {
            $data = $this->toArray();
            unset($data[$this->_primaryKey]);
            $pdoStmt = $this->insert($this->table, $data);
            if ($pdoStmt->errorInfo() && $pdoStmt->errorCode() !== "00000") {
                return Errors::set($pdoStmt->errorInfo(), $pdoStmt->errorCode());
            }
            return $this->id();
        }

        /**
         * 统计数量
         * @param null $join
         * @param null $column
         * @param null $where
         * @return bool|int|mixed|string
         */
        public function getCount($join = null, $column = null)
        {
            return parent::count($this->table, $join, $column, $this->getWhere());
        }

        /**
         * 获取一条记录
         * @param null $join
         * @param null $columns
         * @param null $where
         * @return array|mixed
         */
        public function first($columns = "*", $join = [])
        {
            if (empty($join)) {
                return parent::get($this->table, $columns, $this->getWhere());
            }
            return parent::get($this->table, $join, $columns, $this->getWhere());
        }

        /**
         * 是否存在记录
         * @param $join
         * @return bool
         */
        public function exist($join = null)
        {
            if ($join) {
                return parent::has($this->table, $join, $this->getWhere());
            }
            return parent::has($this->table, $this->getWhere());
        }

        /**
         * 删除数据
         * @return bool
         */
        public function remove()
        {
            $where = $this->getWhere();
            if (empty($where)) {
                return Errors::set("Not allow to delete all data", "-1");
            }
            $result = $this->delete($this->table, $where);
            if ($result->errorCode() !== '00000') {
                return Errors::set($result->errorInfo(), $result->errorCode());
            }
            return true;
        }

        /**
         * 更新数据
         * @param $data
         * @return bool|\PDOStatement
         */
        public function change($data)
        {
            unset($data[$this->_primaryKey]);
            $where = $this->getWhere();
            if (empty($where)) {
                return Errors::set("Not allow to update all data", "-1");
            }
            $result = $this->update($this->table, $data, $where);
            $count = $result->rowCount();
            if ($result->errorCode() !== '00000') {
                return Errors::set($result->errorInfo(), $result->errorCode());
            }
            return $count === 0 ? true : $count;
        }

        /**
         * 获取分页结果
         * @param string $colunms
         * @param int $page
         * @param int $limit
         * @return array
         */
        public function getList($join = null, $colunms = null, $page = 0, $limit = 10)
        {
            if ($join) {
                $total = $this->count($this->table, $join, "*", $this->getWhere());
            } else {
                $total = $this->count($this->table, $this->getWhere());
            }
            $return = [
                "list" => [],
                "total" => $total,
                "page" => intval($page),
                "totalPage" => ceil($total / $limit),
            ];
            if ($total > 0) {
                $return["list"] = $this->getLast($join, $colunms, $page, $limit);
            }
            return $return;
        }

        /**
         * 获取数据库表名，可以被重写做分表操作
         * @return mixed|string
         */
        public function getTableName()
        {
            if (empty($this->table)) {
                $table = explode("\\", str_replace("model", "", strtolower(static::class)));
                if (count($table) == 2 && $table[0] == strtolower($table[1])) {
                    array_shift($table);
                }
                $this->table = join("_", $table);
                $this->table = strtolower($this->table);
            }
            return $this->table;
        }


        /**
         * 获取where条件
         * @return array
         */
        private function getWhere()
        {
            $where = $this->_where;
            $this->_where = [];
            return $where;
        }


        /**
         * 映射数据
         * @param $data
         */
        public function map($data)
        {
            foreach ($data as $key => $value) {
                $name = $this->convertUnderline($key);
                if (property_exists($this, $name)) {
                    $this->$name = $value;
                }
            }
        }

        // 下划线变量转驼峰命名
        private function convertUnderline($str)
        {
            $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
                return strtoupper($matches[2]);
            }, $str);
            return $str;
        }
    }
}