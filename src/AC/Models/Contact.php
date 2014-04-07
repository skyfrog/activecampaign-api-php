<?php

namespace AC\Models;

use \stdClass,
    \DateTime,
    \InvalidArgumentException;

class Contact extends Base
{
    protected $email = null;
    protected $subscriberid = null;
    protected $sdate = null;
    protected $status = null;
    protected $firstName = null;
    protected $lastName = null;
    protected $hash = null;
    protected $listId = null;
    protected $lists = array();
    protected $fields = array();

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

    public function setSubscriberid($id)
    {
        $this->subscriberid = (int) $id;
        return $this;
    }

    public function getSubscriberid()
    {
        if ($this->subscriberid === null && $this->id !== null)
            $this->setSubscriberid($this->id);
        return $this->subscriberid;
    }

    public function setStatus($status)
    {
        $this->status = (int) $status;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getApiFieldArray()
    {
        $return = array();
        foreach ($this->fields as $field)
        {
            $return['field['.$field->getTag().',0]'] = $field->getVal();
        }
        return $return;
    }

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

    public function setFields($mixed)
    {
        foreach ($mixed as $field)
        {
            $this->addField($field);
        }
        return $this;
    }

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

    public function getEmail()
    {
        return $this->email;
    }

} 