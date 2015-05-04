<?php
namespace AC\Models;

abstract class Base
{
    /**
     * @var int
     */
    protected $id = null;

    /**
     * @var array
     */
    protected $minArray = array();

    /**
     * @var array
     */
    protected $fullArray = array();

    /**
     * @param null|array|\Traversable|\stdClass $mixed
     * @throws \InvalidArgumentException
     */
    public function __construct($mixed = null)
    {
        if ($mixed)
            $this->loadBulk($mixed);
    }

    /**
     * @param bool $full
     * @return array
     * @throws \RuntimeException
     */
    public function toArray($full = false)
    {
        $array = array();
        foreach ($this->minArray as $property => $key)
        {
            if ($this->{$property} === null)
            {
                throw new \RuntimeException(
                    sprintf(
                        'Not all required data is set: %s (%s) is null',
                        $property,
                        $key
                    )
                );
            }
            $array[$key] = $this->{$property};
        }
        if ($full === false)
            return $array;
        foreach ($this->fullArray as $property => $key)
        {
            if ($this->{$property} !== null)
            {
                $array[$key] = $this->{$property};
            }
        }
        return $array;
    }

    /**
     * @param array|\Traversable|\stdClass $mixed
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function loadBulk($mixed)
    {
        if (!is_array($mixed) && !is_object($mixed)) {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s::%s requires an object or array to set bulk',
                    get_class($this),
                    __FUNCTION__
                )
            );
        }
        if (is_object($mixed) && !$mixed instanceof \Traversable && !$mixed instanceof \stdClass) {
            throw new \InvalidArgumentException(
                'Bulk object should be Traversable or instance of stdClass'
            );
        }
        foreach ($mixed as $k => $v) {
            $setter = 'set' . implode(
                    '',
                    array_map(
                        'ucfirst',
                        explode(
                            '_',
                            $k
                        )
                    )
                );
            if (method_exists($this, $setter)) {
                $this->{$setter}($v);
            }
        }
        return $this;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->id)
            return (string) $this->id;
        return '';
    }
} 