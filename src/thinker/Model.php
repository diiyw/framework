<?php

namespace thinker\model;

class Model
{
    private $_where = [];

    private $_orderBy = [];

    private $_groupBy = [];

    private $_limit = 10;

    private $_page = 0;

    private $_binds = [];

    private $_colunms = "";

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
     * 新建模型
     * @param $model
     */
    public function __construct($mapper = [])
    {
        $objName = "CONN::" . $this->name;
        $config = Registry::get("db")[$this->name];
        if (!Registry::get($objName) instanceof \PDO) {
            Registry::set($objName, new \PDO(
                $config['dsn'], $config['user'], $config['password'],
                [\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']
            ));
        }
        $this->conn = Registry::get($objName);
    }

    /**
     * 条件
     * $where = [
     *    "id"=> ["=",3],
     * ]
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
     * 选择字段
     * @param $start
     * @param int $number
     */
    public function select($colunms = "*")
    {
        $this->colunms = $colunms;
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
            $this->binds[] = $item[1];
            $where .= " AND " . $field . $item[0] . "?";
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
        $this->query("INSERT INTO " . $this->table . "(" . join(",", $fields) . ")VALUES(" . $bind . ")");
        return $this->conn->lastInsertId();
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
        $sql = "UPDATE " . $this->table . " SET " . $sets . " WHERE " . $this->buildSql();
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
        $sql = "DELETE FROM " . $this->table . $this->buildSql();
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
    public function get()
    {
        $sql = "SELECT " . $this->_colunms . " FROM " . $this->table . $this->buildSql();
        $result = $this->query($sql);
        return $result->fetchAll();
    }

    public function first()
    {
        $sql = "SELECT " . $this->_colunms . " FROM " . $this->table . $this->buildSql();
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
        $result = $this->conn->prepare($sql);
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
     * @param callable $transaction
     * @return bool
     */
    public function transaction(callable $tx)
    {
        $this->conn->beginTransaction();
        $result = $tx();
        if ($result) {
            $this->conn->commit();
            return $result;
        }
        $this->conn->rollBack();
        return false;
    }

    /**
     * Pdo查询结果转换到对象操作
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->{convertUnderline($name)} = $value;
    }

    protected function convertUnderline($str)
    {
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);
        return $str;
    }
}