<?php

namespace AC\Models;

use \stdClass,
    \DateTime,
    \InvalidArgumentException;

class Contact extends Base
{
    /**
     * @var string
     */
    protected $email = null;

    /**
     * @var Int
     */
    protected $subscriberid = null;

    /**
     * @var DateTime
     */
    protected $sdate = null;

    /**
     * @var int
     */
    protected $status = null;

    /**
     * @var string
     */
    protected $firstName = null;

    /**
     * @var string
     */
    protected $lastName = null;

    /**
     * @var string
     */
    protected $hash = null;

    /**
     * @var int
     */
    protected $listId = null;

    /**
     * @var array
     */
    protected $lists = array();

    /**
     * @var array
     */
    protected $fields = array();

    /**
     * @param $id
     * @return $this
     */
    public function setContactId($id)
    {
        return $this->setSubscriberid($id);
    }

    /**
     * @return Int|null
     */
    public function getContactId()
    {
        return $this->getSubscriberid();
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param string $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setSubscriberid($id)
    {
        $this->subscriberid = (int) $id;
        return $this;
    }

    /**
     * @return Int|null
     */
    public function getSubscriberid()
    {
        if ($this->subscriberid === null && $this->id !== null)
            $this->setSubscriberid($this->id);
        return $this->subscriberid;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = (int) $status;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param string $title
     * @return Field|null
     */
    public function getFieldByTitle($title)
    {
        $return = null;
        /** @var Field $field */
        foreach ($this->fields as $field)
        {
            if ($field->getTitle() === $title)
            {
                $return = $field;
                break;
            }
        }
        return $return;
    }

    /**
     * @param string $tag
     * @return Field|null
     */
    public function getFieldByTag($tag)
    {
        $return = null;
        /** @var Field $field */
        foreach ($this->fields as $field)
        {
            if ($field->getTag() === $tag)
            {
                $return = $field;
                break;
            }
        }
        return $return;
    }

    /**
     * @param string $property
     * @param mixed $value
     * @return Field|null
     */
    public function getFieldByProperty($property, $value)
    {
        $return = null;
        $tmp = new Field();
        $getter = 'get'.implode(
                '',
                array_map(
                    'ucfirst',
                    explode(
                        '_',
                        $property
                    )
                )
            );
        if (!method_exists($tmp, $getter))
        {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s does not have a %s property (%s getter not found)',
                    get_class($tmp),
                    $property,
                    $getter
                )
            );
        }
        /** @var Field $field */
        foreach ($this->fields as $field)
        {
            if ($field->{$getter}() === $value)
            {
                $return = $field;
                break;
            }
        }
        return $return;
    }

    /**
     * @param string $property
     * @param mixed $value
     * @return Field|null
     */
    public function getFieldByGetter($getter, $value)
    {
        $return = null;
        $tmp = new Field();
        if (!method_exists($tmp, $getter))
        {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s does not have a %s method',
                    get_class($tmp),
                    $getter
                )
            );
        }
        /** @var Field $field */
        foreach ($this->fields as $field)
        {
            if ($field->{$getter}() === $value)
            {
                $return = $field;
                break;
            }
        }
        return $return;
    }

    /**
     * @return array
     */
    public function getApiFieldArray()
    {
        $return = array();
        /** @var Field $field */
        foreach ($this->fields as $field)
        {
            $return['field['.$field->getTag().',0]'] = $field->getVal();
        }
        return $return;
    }

    /**
     * @param null|int $list
     * @return array
     */
    public function getApiArray($list = null)
    {
        if ($list === null)
            $list = $this->getListId();
        $return = array(
            'email'             => $this->getEmail(),
            'first_name'        => $this->getFirstName(),
            'last_name'         => $this->getLastName(),
            'p['.$list.']'      => $list,
            'status['.$list.']' => $this->getStatus()
        );
        return array_merge(
            $return,
            $this->getApiFieldArray()
        );
    }

    /**
     * @param array|stdClass|\Traversable $mixed
     * @return $this
     */
    public function setFields($mixed)
    {
        foreach ($mixed as $field)
        {
            $this->addField($field);
        }
        return $this;
    }

    /**
     * @param stdClass $field
     * @return $this
     */
    public function addField(stdClass $field)
    {
        $this->fields[$field->id] = new Field($field);
        return $this;
    }

    /**
     * @param string|DateTime $date
     * @return $this
     */
    public function setSdate($date)
    {
        $this->sdate = $date instanceof DateTime ? $date : new DateTime($date);
        return $this;
    }

    /**
     * @param bool $asString = false
     * @return null|string|DateTime
     */
    public function getSdate($asString = false)
    {
        if ($this->sdate === null)
            return null;
        if ($asString === true)
            return $this->sdate->format('Y-m-d H:i:s');
        return $this->sdate;
    }

    /**
     * @param integer $id
     * @return $this
     */
    public function setListId($id)
    {
        $this->listId = (int) $id;
        $this->lists[$this->listId] = $this->listId;
        return $this;
    }

    /**
     * @return null|int
     */
    public function getListId()
    {
        return $this->listId;
    }

    public function setListslist($list)
    {
        $list = explode(',',$list);
        foreach ($list as $l)
        {
            $l = (int) $l;
            $this->lists[$l] = $l;
        }
        return $this;
    }

    /**
     * @param string|null $email
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setEmail($email)
    {
        if ($email && !filter_var($email, \FILTER_VALIDATE_EMAIL))
        {
            throw new InvalidArgumentException(
                sprintf(
                    '%s expects valid email address, %s is invalid',
                    __FUNCTION__,
                    $email
                )
            );
        }
        $this->email = $email;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

} 