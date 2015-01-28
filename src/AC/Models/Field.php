<?php

namespace AC\Models;

use \DateTime;

class Field extends Base
{
    const CHECKBOX_DELIMITER = '||';

    protected $title = null;
    protected $perstag = null;
    protected $visible = null;
    protected $val = null;
    protected $relid = null;
    protected $type = null;
    protected $options = array();
    protected $tag = null;
    protected $custom = false;

    /**
     * @param bool $custom
     * @return $this
     */
    public function setCustom($custom)
    {
        $this->custom = (bool) $custom;
        return $this;
    }

    /**
     * @return bool
     */
    public function getCustom()
    {
        return $this->custom;
    }

    /**
     * @return bool
     */
    public function isCustom()
    {
        return $this->custom;
    }

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param null $perstag
     */
    public function setPerstag($perstag)
    {
        $this->perstag = $perstag;
        return $this;
    }

    /**
     * @return null
     */
    public function getPerstag()
    {
        return $this->perstag;
    }

    /**
     * @param null $relid
     */
    public function setRelid($relid)
    {
        $this->relid = (int) $relid;
        return $this;
    }

    /**
     * @return null
     */
    public function getRelid()
    {
        return $this->relid;
    }

    /**
     * @param null $tag
     */
    public function setTag($tag)
    {
        $this->tag = $tag;
        return $this;
    }

    /**
     * @return null
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param null $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param null $type
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param null $val
     */
    public function setVal($val)
    {
        if ($val === null)
        {
            $this->val = null;
            return $this;
        }
        switch ($this->type)
        {
            case 'date':
                $this->val = new DateTime($val);
                break;
            case 'checkbox':
                if (is_array($val))
                    $val = implode(self::CHECKBOX_DELIMITER, $val);
                $this->val = $val;
                break;
            default:
                $this->val = $val;
        }
        return $this;
    }

    /**
     * @return null
     */
    public function getVal($asObject = false)
    {
        if ($this->val === null)
            return null;
        if ($asObject)
        {
            switch ($this->type)
            {
                case 'date':
                    if (!$this->val instanceof DateTime)
                        $this->val = new DateTime($this->val);
                    return $this->val;
                case 'checkbox':
                    if (!is_array($this->val))
                        return explode(self::CHECKBOX_DELIMITER, $this->val);
                    return $this->val;
            }
        }
        $val = $this->val;
        if ($val instanceof DateTime)
            $val = $val->format('Y-m-d H:i:s');
        if (is_array($val))
            $this->setVal(implode(self::CHECKBOX_DELIMITER, $val));
        return $this->val;
    }

    /**
     * @param null $visible
     */
    public function setVisible($visible)
    {
        $this->visible = (int) $visible;
        return $this;
    }

    /**
     * @return null
     */
    public function getVisible()
    {
        return $this->visible;
    }

} 