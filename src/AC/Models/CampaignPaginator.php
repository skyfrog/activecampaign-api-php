<?php

namespace AC\Models;
use AC\Models\Interfaces\Paginator;


class CampaignPaginator extends Base implements Paginator
{
    const LIMIT_TEN = 10;
    const LIMIT_TWENTY = 20;
    const LIMIT_FIFTY = 50;
    const LIMIT_HUNDRED = 100;

    /**
     * @var int
     */
    protected $offset = 0;

    /**
     * @var int
     */
    protected $limit = self::LIMIT_HUNDRED;

    /**
     * @var int
     */
    protected $total = 0;

    /**
     * @var int
     */
    protected $cnt = 0;

    /**
     * @var array
     */
    protected $rows = null;

    /**
     * @var string
     */
    protected $filter = '0';

    /**
     * @var string
     */
    protected $public = '1';

    /**
     * @var string
     */
    protected $sort = '';

    /**
     * @param mixed $p
     * @return $this
     */
    public function setPublic($p)
    {
        $this->public = (int) $p ? '1' : '0';
        return $this;
    }

    /**
     * @return string
     */
    public function getPublic()
    {
        return $this->public;
    }

    /**
     * @param $filter
     * @return $this
     */
    public function setFilter($filter)
    {
        $this->filter = (string) $filter;
        return $this;
    }

    /**
     * @return string
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @param string $sort
     * @return $this
     */
    public function setSort($sort)
    {
        $this->sort = (string) $sort;
        return $this;
    }

    /**
     * @return string
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param int $cnt
     * @return $this
     */
    public function setCnt($cnt)
    {
        $this->cnt = (int) $cnt;
        return $this;
    }

    /**
     * @return int
     */
    public function getCnt()
    {
        return $this->cnt;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $offset
     * @return $this
     */
    public function setOffset($offset)
    {
        $this->offset = (int) $offset;
        return $this;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param array $rows
     * @return $this
     */
    public function setRows(array $rows)
    {
        $this->rows = array();
        foreach ($rows as $elem)
        {
            if (!$elem instanceof Campaign)
                $elem = new Campaign(
                    $elem
                );
            $this->rows[] = $elem;
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * @param int $total
     * @return $this
     */
    public function setTotal($total)
    {
        $this->total = (int) $total;
        return $this;
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param $mixed
     * @return $this
     * @throws \RuntimeException
     */
    public function setResponse($mixed)
    {
        if (!$mixed)
            return $this;
        if ($mixed instanceof \stdClass)
        {
            if (!isset($mixed->result_code) || !isset($mixed->success) || !$mixed->success)
                throw new \RuntimeException(
                    sprintf(
                        'Error response: %s',
                        isset($mixed->result_message) ? $mixed->result_message : $mixed->error_message
                    )
                );
            $mixed = (array) $mixed;
        }
        if (is_array($mixed) || $mixed instanceof \Traversable)
        {
            foreach ($mixed as  $k => $v)
            {
                $k = 'set'.implode(
                        '',
                        array_map(
                            'ucfirst',
                            explode(
                                '_',
                                $k
                            )
                        )
                    );
                if (method_exists($this, $k))
                {
                    $this->{$k}($v);
                }
            }
        }
        return $this;
    }


    /**
     * @return array|mixed
     */
    public function getData()
    {
        return $this->getRows();
    }

    /**
     * @return bool
     */
    public function canPaginateFurther()
    {
        $pp = $this->cnt + $this->offset;
        if ($pp < $this->total)
            return true;
        return false;
    }

    /**
     * @return $this
     */
    public function setNextPage()
    {
        if ($this->cnt === $this->limit && $this->total%$this->limit)
        {
            $this->cnt = 0;
            $this->setRows(array())
                ->setOffset(
                    $this->offset + $this->limit
                );
            return $this;
        }
        $this->cnt = 0;
        $this->offset = 0;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = array(
            'offset' => 0,
            'limit'  => 100,
            'filter' => '0',
            'public' => '1'
        );
        if ($this->getSort())
            $array['sort'] = $this->getSort();
        return $array;
    }
}