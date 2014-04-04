<?php
namespace AC\Models;


abstract class Base
{
    protected $id = null;

    protected $minArray = array();
    protected $fullArray = array();

    /**
     * @param bool $full
     * @return array
     * @throws \RuntimeException
     */
    final public function toArray($full = false)
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