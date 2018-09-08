<?php

namespace Mapper;

abstract class Mapper
{
    public $where = [];

    public $orderBy = "";

    public $groupBy = "";

    public $limit = "";

    /**
     * ����
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
     * ���Ʋ�ѯ
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
     * ����
     * @param $by
     */
    public function groupBy($by)
    {
        $this->groupBy = $by;
    }

    /**
     * ����
     * @param $by
     */
    public function orderBy($by)
    {
        $this->orderBy = $by;
    }

    abstract function toArray();
}