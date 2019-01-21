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
                $options = App::loadConfig($module . ".db", $this->__name);
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
            $this->_where = array_merge($this->_where, $conditions);
        }

        /**
         * 返回Select结果
         */
        public function result($colunms = "*")
        {
            return $this->select($this->table, $colunms, $this->_where);
        }

        /**
         * 获取数据库最新的记录
         */
        public function getLast($colunms = "*", $page = 0, $limit = 10)
        {
            $sql["LIMIT"] = [$page * $limit, $limit];
            $sql["AND"] = $this->_where;
            return $this->select($this->table, $colunms, $sql);
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
            return parent::count($this->table, $join, $column, $this->_where);
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
                return parent::get($this->table, $columns, $this->_where);
            }
            return parent::get($this->table, $join, $columns, $this->_where);
        }

        /**
         * 是否存在记录
         * @param $join
         * @return bool
         */
        public function exist($join = null)
        {
            if ($join) {
                return parent::has($this->table, $join, $this->_where);
            }
            return parent::has($this->table, $this->_where);
        }

        /**
         * 删除数据
         * @return bool
         */
        public function remove()
        {
            $result = $this->delete($this->table, $this->_where);
            if (empty($this->_where)) {
                return Errors::set("Not allow to delete all data", "-1");
            }
            if ($result->errorCode !== '00000') {
                return Errors::set($result->errorInfo(), $result->errorCode);
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
            $result = $this->update($data, $this->_where);
            if (empty($this->_where)) {
                return Errors::set("Not allow to update all data", "-1");
            }
            if ($result->errorCode !== '00000') {
                return Errors::set($result->errorInfo(), $result->errorCode);
            }
            return true;
        }

        /**
         * 获取分页结果
         * @param string $colunms
         * @param int $page
         * @param int $limit
         * @return array
         */
        public function getList($colunms = "*", $page = 0, $limit = 10)
        {
            $total = $this->count($this->table, "1");
            $return = [
                "list" => [],
                "total" => $total,
                "page" => $page,
                "totalPage" => ceil($total / $limit),
            ];
            if ($total > 0) {
                $this->getLast($colunms, $page, $limit);
            }
            return $return;
        }

        public function getTableName()
        {
            if (empty($this->table)) {
                $table = explode("\\", static::class);
                if (count($table) == 2) {
                    array_shift($table);
                }
                $this->table = str_replace("Model", "", join("_", $table));
                $this->table = strtolower($this->table);
            }
            return $this->table;
        }
    }
}