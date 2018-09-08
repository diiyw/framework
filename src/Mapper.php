<?php

namespace Mapper;

abstract class Mapper
{
    public $where = [];

    public $orderBy = "";

    public $groupBy = "";

    public $limit = "";

    /**
     * 条件
     * @param array $where
     */
    public function where(array $where)
    {
        if (empty($where)) {
            return;
        }
        foreach ($where as $field => $condition) {
            $this->where[$condition[0] . " : " . $field] = $condition[1];
        }
    }

    /**
     * 限制查询
     * @param $start
     * @param int $number
     */
    public function limit($start, $number = 0)
    {
        if ($number == 0) {
            $this->limit = " 10 ";
            return;
        }
        $this->limit = "$start,$number";
    }

    /**
     * 分组
     * @param $by
     */
    public function groupBy($by)
    {
        $this->groupBy = $by;
    }

    /**
     * 排序
     * @param $by
     */
    public function orderBy($by)
    {
        $this->orderBy = $by;
    }

    abstract function toArray();
}