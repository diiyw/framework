<?php

namespace thinker;

use Mapper\Mapper;

abstract class Model
{
    /**
     * ���ݱ�
     * @var string
     */
    public $table;

    /**
     * PDO���Ӷ���
     * @var \PDO
     */
    public $conn;

    /**
     * ��������
     * @var string
     */
    public $primaryKey;

    /**
     * ���ݱ�ӳ���ϵ
     * @var Mapper
     */
    private $mapper;

    /**
     * ģ�ͳ�ʼ��
     * Model constructor.
     * @param $conn
     * @param $mapper
     */
    public function __construct($conn)
    {
        $this->conn = $conn;
        if (method_exists($this, "initlize")) {
            $this->initlize();
        }
    }

    /**
     * ��������
     * @return bool|integer
     */
    public function insert()
    {
        if (empty($this->mapper)) {
            return false;
        }
        $data = $this->mapper->toArray();
        $fields = array_keys($data);
        $bind = trim(join(",:", $fields), ",");
        $sql = "INSERT INTO " . $this->table . "(" . join(",", $fields) . ")VALUES(" . $bind . ")";
        $this->query($sql, $data);
        return $this->conn->lastInsertId();
    }

    /**
     * ��������
     * @return int
     * @throws \Exception
     */
    public function update()
    {
        $mapper = $this->mapper->toArray();
        if (empty($mapper) || empty($this->mapper->where)) {
            return 0;
        }
        $sets = "";
        foreach ($mapper as $field => $value) {
            if ($field == $this->primaryKey) {
                continue;
            }
            $sets .= "$field=:$field,";
        }
        $where = "";
        $data = [];
        if (!empty($this->mapper[$this->primaryKey])) {
            $where = $this->primaryKey . "=" . $this->mapper[$this->primaryKey];
            $data = $mapper;
        } else {
            foreach ($this->mapper->where as $condition => $value) {
                $where .= " AND " . $condition;
                $data[] = $value;
            }
        }
        $sets = trim($sets, ",");
        $sql = "UPDATE " . $this->table . " SET " . $sets . " WHERE " . $where;
        $result = $this->query($sql, $data);
        return $result->rowCount();
    }

    /**
     * ɾ������
     * @return int
     */
    public function delete()
    {
        $mapper = $this->mapper->where;
        if (empty($mapper)) {
            return 0;
        }
        $where = "";
        $data = [];
        foreach ($mapper as $field => $value) {
            if ($value != "") {
                $where .= "$field AND";
                $data[] = $value;
            }
        }
        $where = trim($where, "AND");
        $sql = "DELETE FROM " . $this->table . " WHERE " . $where;
        $result = $this->query($sql, $data);
        return $result->rowCount();
    }

    /**
     * ���ݲ�ѯ
     * @param $colunms
     * @param bool $all
     * @return array|mixed
     * @throws \Exception
     */
    public function select($colunms, $all = true)
    {
        $mapper = $this->mapper->where;
        if (empty($mapper)) {
            return [];
        }
        $where = "";
        $data = [];
        foreach ($this->mapper as $field => $value) {
            if ($value != "") {
                $where .= "$field=:$field AND";
                $data[] = $value;
            }
        }
        $where = trim($sets, "AND");
        $sql = "SELECT " . $colunms . " FROM " . $this->table . " WHERE " . $where;
        $result = $this->query($sql, $data);
        return $all ? $result->fetchAll() : $result->fetch();
    }

    /**
     * ӳ���ϵ
     * @param $mapper
     */
    public function map($mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * ִ��SQl���
     * @param $sql
     * @param array $data
     * @return \PDOStatement
     * @throws \Exception
     */
    public function query($sql, $data = array())
    {
        $result = $this->conn->prepare($sql);
        foreach ($data as $key => &$value) {
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
    public function transaction(callable $transaction)
    {
        $this->conn->beginTransaction();
        $result = $transaction();
        if ($result) {
            $this->conn->commit();
            return $result;
        }
        $this->conn->rollBack();
        return false;
    }
}