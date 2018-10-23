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
     * ���ݱ�
     * @var string
     */
    protected $_table;

    /**
     * PDO���Ӷ���
     * @var \PDO
     */
    protected $_conn;

    /**
     * Ĭ����������
     * @var string
     */
    protected $_name = "default";

    /**
     * �½�ģ��
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
     * ����
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
     * ���Ʋ�ѯ
     * @param $start
     * @param int $number
     */
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * ѡ���ֶ�
     * @param $start
     * @param int $number
     */
    public function select($colunms = "*")
    {
        $this->colunms = $colunms;
        return $this;
    }

    /**
     * ��ҳ
     * @param $page
     * @param int $number
     */
    public function page($page)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * ����
     * @param $by
     */
    public function groupBy($by)
    {
        $this->groupBy = $by;
        return $this;
    }

    /**
     * ����
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
     * ��������
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
     * ��������
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
     * ɾ������
     * @return int
     */
    public function delete()
    {
        $sql = "DELETE FROM " . $this->table . $this->buildSql();
        $result = $this->query($sql);
        return $result->rowCount();
    }

    /**
     * ���ݲ�ѯ
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
     * ִ��SQl���
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
     * ������
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
     * Pdo��ѯ���ת�����������
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