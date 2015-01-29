<?php
namespace AC\Models;


class Filter extends Base
{
    const MODE_STRICT = '';
    const MODE_SINCE = 'since_';
    const MODE_UNTIL = 'until';

    const GET_FULL = 1;
    const GET_ABBR = 0;

    const SORT_ASC  = 'ASC';
    const SORT_DESC = 'DESC';

    /**
     * @var CList
     */
    protected $list = null;

    /**
     * @var array
     */
    protected $fields = array();

    /**
     * @var array
     */
    protected $modes = array();

    /**
     * @var int
     */
    protected $full = self::GET_ABBR;

    /**
     * @var string
     */
    protected $sort = 'id';

    /**
     * @var string
     */
    protected $sortDirection = self::SORT_DESC;

    /**
     * @var int
     */
    protected $page = 1;

    /**
     * @param int $full
     * @return $this
     */
    public function setFull($full)
    {
        $this->full = (int) ((bool) $full);//ensure 1 or 0
        return $this;
    }

    /**
     * @return int
     */
    public function getFull()
    {
        return $this->full;
    }

    /**
     * @param int $page
     * @return $this
     */
    public function setPage($page)
    {
        $this->page = abs((int) $page);
        return $this;
    }

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @return $this
     */
    public function setNextPage()
    {
        ++$this->page;
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setSort($name)
    {
        $this->sort = $name;
        return $this;
    }

    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param string $sort
     * @return $this
     */
    public function setSortDirection($sort)
    {
        if ($sort !== self::SORT_DESC && $sort !== self::SORT_ASC)
        {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s is not a valid SORT mode (use %s::SORT_* constants)',
                    (string) $sort,
                    __CLASS__
                )
            );
        }
        $this->sortDirection = $sort;
        return $this;
    }

    /**
     * @return string
     */
    public function getSortDirection()
    {
        return $this->sortDirection;
    }

    /**
     * @param array $fields
     * @param array $modes = null
     * @return $this
     */
    public function setFields(array $fields, array $modes = null)
    {
        if ($modes)
            $this->modes = $modes;
        $this->fields = array();
        foreach ($fields as $k => $field)
        {
            if (!$field instanceof Field)
            {
                $field = new Field($field);
            }
            $this->fields[$k] = $field;
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    public function setModes(array $modes)
    {
        $this->modes = $modes;
        return $this;
    }

    /**
     * @return array
     */
    public function getModes()
    {
        return $this->modes;
    }

    /**
     * @param CList|int $list
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setList($list)
    {
        if (!$list instanceof CList)
        {
            if (!is_numeric($list) && !is_string($list))
            {
                throw new \InvalidArgumentException(
                    sprintf(
                        '%s expects a CList instance, string, or int, %s given',
                        __METHOD__,
                        is_object($list) ? get_class($list) : gettype($list)
                    )
                );
            }
            $list = new CList(
                array(
                    'stringId'    => $list
                )
            );
        }
        if (!$list->getStringId() && $list->getId()) {
            $list->setStringId(
                $list->getId()
            );
        }
        $this->list = $list;
        return $this;
    }

    /**
     * @param bool $id = false
     * @return CList|int
     */
    public function getList($id = false)
    {
        if ($id === true)
            return $this->list->getStringId();
        return $this->list;
    }

    /**
     * @return array
     */
    public function getFieldFilterArray()
    {
        $fieldArray = array(
            'fields'    => array()
        );
        /** @var Field $field */
        foreach ($this->fields as $k => $field)
        {
            $key = sprintf(
                '%s%s',
                isset($this->modes[$k]) ? $this->modes[$k] : self::MODE_STRICT,
                $field->getTag()
            );
            if ($field->isCustom())
            {
                $fieldArray['fields'][$key] = $field->getVal();
            }
            else
            {
                $fieldArray[$key] = $field->getVal();
            }
        }
        return $fieldArray;
    }

    /**
     * This implementation of toArray IGNORES $full
     * @param bool $full = false
     * @return array
     */
    public function toArray($full = false)
    {
        $filters = $this->getFieldFilterArray();
        $array = array(
            'ids'               => $this->getList(true),
            'full'              => $this->getFull(),
            'sort'              => $this->getSort(),
            'sort_direction'    => $this->getSortDirection(),
            'page'              => $this->getPage()
        );
        foreach ($filters as $k => $val)
        {
            if ($k === 'fields' && $val)
            {
                foreach ($val as $custom => $value)
                {
                    $mainKey = sprintf(
                        'filters[fields][%s]',
                        $custom
                    );
                    $array[$mainKey] = $value;
                }
            }
            else
            {
                $mainKey = sprintf(
                    'filters[%s]',
                    $k
                );
                $array[$mainKey] = $val;
            }
        }
        return $array;
    }
}
