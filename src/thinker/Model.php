<?php

namespace thinker;

use PDO;

class Model
{
    /**
     * 数据表
     * @var mixed|string
     */
    protected $table;

    /**
     * 数据库主键
     * @var string
     */
    protected $primaryKey = "";

    /**
     * 实例单例名称
     * @var string
     */
    protected $__name = "default";

    /**
     * PDO连接实例
     * @var \PDO
     */
    protected $__pdo;

    /**
     * Where条件
     * @var array
     */
    protected $__where = [];

    /**
     * Join条件
     * @var array
     */
    protected $__join = [];

    /**
     * 其它查询条件，Order by etc.
     * @var array
     */
    protected $__other = [];

    /**
     * PDO绑定数据
     * @var array
     */
    protected $__binding = [];

    /**
     * @var \PDOStatement
     */
    protected $_lastStmt;

    /**
     * 新建模型
     * @param array $options
     * @throws Exception
     */
    public function __construct($options = [])
    {
        $this->table = $this->getTableName();
        $namespace = explode("\\", static::class);
        if (empty($options)) {
            $module = array_shift($namespace);
            $moduleConst = $module . "\\" . ucfirst($module) . "Const";
            $options = null !== $moduleConst::DB_CONFIG ? $moduleConst::DB_CONFIG[$this->__name] : [];
        }
        $objName = "DB:CONN:" . $this->__name;
        try {
            try {
                if (!App::load($objName) instanceof PDO) {
                    $this->__pdo = new PDO(
                        $options['dsn'], $options['user'], $options['password'],
                        [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']
                    );
                    App::set($objName, $this->__pdo);
                } else {
                    $this->__pdo = App::load($objName);
                }
            } catch (PDOException $e) {
                $message = $e->getMessage();
                if (empty($message)) {
                    $message = "Connect database failed";
                }
                throw new Exception("PDO:" . $message, $e->getCode());
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            if (empty($message)) {
                $message = "Connect database failed";
            }
            throw new Exception($message, $e->getCode());
        }
    }

    /**
     * 返回Select结果
     * @param string $columns
     * @return array
     */
    public function select($columns = "*"): array
    {
        $selectStmt = "SELECT " . $columns . " FROM " . $this->table;
        if ($this->__join) {
            $selectStmt .= join("", $this->__join);
        }
        if (!empty($this->__where)) {
            $selectStmt .= " WHERE " . join(" AND ", $this->__where);
        }
        if (!empty($this->__other)) {
            $selectStmt .= " " . join(" ", $this->__other);
        }
        return $this->query($selectStmt, $this->__binding)->fetchAll();
    }

    /**
     * 插入一条记录
     * @param $modelData
     * @return string
     */
    public function insert($modelData)
    {
        $columns = join(",", array_keys($modelData));
        $binding = "";
        foreach ($modelData as $key => $datum) {
            $binding .= ":" . $key . ",";
        }
        $binding = rtrim($binding, ",");
        $insertStmt = "INSERT INTO " . $this->table . "($columns)VALUES(" . $binding . ")";
        $this->query($insertStmt, $modelData);
        return $this->__pdo->lastInsertId();
    }

    /**
     * 删除数据
     * @return bool
     */
    public function delete()
    {
        $deleteStmt = "DELETE FROM " . $this->table;
        if (!empty($this->__where)) {
            $deleteStmt .= " WHERE " . join(" AND ", $this->__where);
        }
        $queryStmt = $this->query($deleteStmt, $this->__binding);
        return $queryStmt->rowCount();
    }

    /**
     * 更新数据
     * @param $modelData
     * @return int
     */
    public function update($modelData)
    {
        $bindData = [];
        $updateStmt = "UPDATE " . $this->table . " SET ";
        foreach ($modelData as $key => $datum) {
            if (preg_match("@(\w+)\[([+-=]+)\]@", $key, $match)) {
                $key = $match[1];
                if ($match[2] == "-=") {
                    $updateStmt .= "$key=$key-:$key,";
                }
                if ($match[2] == "+=") {
                    $updateStmt .= "$key=$key+:$key,";
                }
            } else {
                $updateStmt .= "$key=:$key,";
            }
            $bindData[$key] = $datum;
        }
        $updateStmt = rtrim($updateStmt, ",");
        if (!empty($this->__where)) {
            $updateStmt .= " WHERE " . join(" AND ", $this->__where);
        }
        $this->__binding = array_merge($bindData, $this->__binding);
        $queryStmt = $this->query($updateStmt, $this->__binding);
        return $queryStmt->rowCount();
    }

    /**
     * 获取数据库最新的记录
     * @param string $columns
     * @param null $join
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function findLast($columns = "*", $join = null, $page = 0, $limit = 10): array
    {
        $this->limit([($page - 1) * $limit, $limit]);
        $this->join($join);
        return $this->select($columns);
    }

    /**
     * 统计数量
     * @param null $join
     * @return bool|int|mixed|string
     */
    public function count($join = null)
    {
        $this->join($join);
        $count = $this->select("COUNT(1)");
        $this->__join = [];
        if (empty($count)) {
            return 0;
        }
        return $count[0]["COUNT(1)"];
    }

    /**
     * 获取一条记录
     * @param string $columns
     * @param null $join
     * @return array
     */
    public function findOne($columns = "*", $join = null): array
    {
        $this->limit(1);
        $this->join($join);
        $result = $this->select($columns);
        if (!empty($result)) {
            return $result[0];
        }
        return [];
    }

    /**
     * 获取分页结果
     * @param string $columns
     * @param null $join
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function findList($columns = "*", $join = null, $page = 0, $limit = 10)
    {
        $total = $this->count($join);
        $return = [
            "list" => [],
            "total" => $total,
            "page" => intval($page),
            "totalPage" => ceil($total / $limit),
        ];
        if ($total > 0) {
            $return["list"] = $this->findLast($columns, $join, $page, $limit);
        }
        return $return;
    }

    /**
     * 构造Where条件
     * @param $where
     * @param string $split
     * @return Model
     */
    public function where($where, $split = " AND ")
    {
        foreach ($where as $key => $value) {
            switch ($key) {
                case "AND":
                    $this->where($value);
                    break;
                case "OR":
                    $where = $this->__where;
                    $this->where($value, " OR ");
                    $where[] = "(" . join(" OR ", $this->__where) . ")";
                    $this->__where = $where;
                    break;
                default:
                    if ($value !== null) {
                        if (preg_match("@(\w+)\[([=<>\?]+)\]@", $key, $match)) {
                            switch ($match[2]) {
                                case "?>":
                                    $value = "%" . $value;
                                case ">?":
                                    $value = $value . "%";
                                case "??":
                                    $this->__binding[$match[1]] = "%" . $value . "%";
                                    $this->__where[] = $match[1] . " LIKE " . $match[2] . ":" . $match[1];
                                    break;
                                case "><":
                                    $this->__binding[$match[1] . "_1"] = $value[0];
                                    $this->__binding[$match[1] . "_2"] = $value[1];
                                    $this->__where[] = $match[1] . " BETWEEN :" . $match[1] . "_1 AND :" . $match[1] . "_2";
                                    break;
                                case "=":
                                    if (is_array($value)) {
                                        $match[2] = " $key IN (";
                                    }
                                case "!=":
                                    if (is_array($value)) {
                                        $match[2] = " $key NOT IN (";
                                    }
                                default:
                                    if (is_array($value)) {
                                        foreach ($value as $k => $v) {
                                            $name = "{$match[1]}_$k";
                                            $match[2] .= ":$name,";
                                            $this->__binding[$name] = $v;
                                        }
                                        $this->__where[] = rtrim($match[2], ',') . ") ";
                                    } else {
                                        $this->__binding[$match[1]] = $value;
                                        $this->__where[] = $match[1] . $match[2] . ":" . $match[1];
                                    }
                            }
                        } else {
                            if (is_array($value)) {
                                $in = " $key IN (";
                                foreach ($value as $k => $v) {
                                    $name = "{$key}_$k";
                                    $in .= ":$name,";
                                    $this->__binding[$name] = $v;
                                }
                                $this->__where[] = rtrim($in, ",") . ")";
                            } else {
                                $this->__binding[$key] = $value;
                                $this->__where[] = $key . "=:$key";
                            }
                        }
                    }
            }
        }
        return $this;
    }

    /**
     * join语句
     * @param $join
     * @return Model
     */
    public function join($join)
    {
        if ($join) {
            foreach ($join as $table => $on) {
                if (preg_match("@(\w+)\[([<>]{2})\]@", $table, $match)) {
                    $method = " UNION ";
                    switch ($match[2]) {
                        case '><':
                            $method = " INNER JOIN ";
                            break;
                        case '>>':
                            $method = " LEFT JOIN ";
                            break;
                        case '<<':
                            $method = " RIGHT JOIN ";
                            break;
                    }
                    $this->__join[] = $method . $match[1] . " ON " . $this->table . "." . key($on) . "=" . $match[1] . "." . $on[key($on)];
                }
            }
        }
        return $this;
    }

    /**
     * Limit语句
     * @param $limit
     * @return string
     */
    public function limit($limit)
    {
        if (is_array($limit)) {
            $this->__other[] = " LIMIT " . intval($limit[0]) . "," . intval($limit[1]);
        } else {
            $this->__other[] = " LIMIT " . intval($limit);
        }
        return $this;
    }


    /**
     * OrderBy语句
     * @param $orderBy
     * @return string
     */
    public function orderBy(array $orderBy)
    {
        $this->__other[] = " ORDER BY " . join(",", $orderBy);
        return $this;
    }

    /**
     * GroupBy语句
     * @param $groupBy
     * @return string
     */
    public function groupBy(array $groupBy): string
    {
        $this->__other[] = " GROUP BY " . join(",", $groupBy);
        return $this;
    }

    /**
     * Having语句
     * @param string $having
     * @return string
     */
    public function having(string $having)
    {
        $this->__other[] = " HAVING " . $having;
        return $this;
    }

    /**
     * 执行SQL
     * @param $sql
     * @param array $data
     * @return bool|\PDOStatement
     */
    public function query($sql, $data = array())
    {
        $queryStmt = $this->__pdo->prepare($sql);
        foreach ($data as $key => &$value) {
            $queryStmt->bindParam($key, $value, is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $queryStmt->setFetchMode(PDO::FETCH_ASSOC);
        $queryStmt->execute();
        if ($queryStmt->errorCode() !== "00000") {
            Errors::set($queryStmt->errorInfo()[2], $queryStmt->errorCode());
            return $queryStmt;
        }
        $this->_lastStmt = $queryStmt;
        return $queryStmt;
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
     * 映射数据
     * @param $data
     */
    public function map($data)
    {
        foreach ($data as $key => $value) {
            $name = $this->camelize($key);
            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }

    /**
     * 下划线变量转驼峰命名
     * @param $str
     * @return string
     */
    public function camelize($str)
    {
        $str = str_replace("_", " ", strtolower($str));
        return lcfirst(str_replace(" ", "", ucwords($str)));
    }

    /**
     * 驼峰命名转下划线
     * @param $str
     * @return string
     */
    public function uncamelize($str)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1_$2", $str));
    }

    /**
     * 模型转化到数组
     * @return array
     * @throws \ReflectionException
     */
    public function toArray()
    {
        $data = [];
        $objects = new \ReflectionClass($this);
        foreach ($objects->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getValue($this) !== null) {
                $name = $this->uncamelize($property->name);
                $data[$name] = $property->getValue($this);
            }
        }
        return $data;
    }

    /**
     * 事务处理
     * @param callable $fn
     * @return bool
     */
    public function action(callable $fn)
    {
        $this->__pdo->beginTransaction();
        try {
            if ($fn()) {
                $this->__pdo->commit();
                return true;
            }
            $this->__pdo->rollBack();
            return false;
        } catch (\Exception $e) {
            $this->__pdo->rollBack();
        }
    }

    /**
     * sql执行错误信息
     */
    public function errorInfo()
    {
        return $this->_lastStmt ? $this->_lastStmt->errorInfo() : [];
    }
}