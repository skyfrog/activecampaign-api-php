<?php
namespace AC\Models;

use \stdClass;

abstract class Base
{
    protected $id = null;

    protected $minArray = array();
    protected $fullArray = array();

    public function __construct($mixed = null)
    {
        if (!$mixed)
            return $this;
        if ($mixed instanceof stdClass)
            $mixed = (array) $mixed;
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

    public function setId($id)
    {
        $this->id = (int) $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function __toString()
    {
        if ($this->id)
            return (string) $id;
        return '';
    }
} 