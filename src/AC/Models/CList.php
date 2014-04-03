<?php
namespace AC\Models;


class CList extends Base
{
    //basics, mainly these properties will be used...
    protected $name = null;
    protected $senderName = null;
    protected $senderAddr1 = null;
    protected $senderCity = null;
    protected $senderZip = null;
    protected $senderCountry = null;

    protected $stringId = null;
    protected $subscriptionNotify = '';
    protected $unsubscriptionNotify = '';
    protected $toName = '';
    protected $carbonCopy = '';


    protected $minArray = array(
        'name'          => 'name',
        'senderName'    => 'sender_name',
        'senderAddr1'   => 'sender_addr1',
        'senderCity'    => 'sender_city',
        'senderZip'     => 'sender_zip',
        'senderCountry' => 'sender_country'
    );

    protected $fullArray = array(
        'subscriptionNotify'    => 'subscription_notify',
        'stringId'              => 'stringid',
        'unsubscriptionNotify'  => 'unsubscription_notify',
        'toName'                => 'to_name',
        'carbonCopy'            => 'carboncopy'
    );

    public function __construct(array $data = null)
    {
        if (!$data)
            return $this;
        foreach ($data as $k => $v)
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

    /**
     * @param string $carbonCopy
     */
    public function setCarbonCopy($carbonCopy)
    {
        if (is_array($carbonCopy))
        {
            foreach ($carbonCopy as $k => $cc)
            {

            }
        }
        $this->carbonCopy = $carbonCopy;
    }

    public function addCarbonCopy($email)
    {
        if (!filter_var($email, \FILTER_VALIDATE_EMAIL))
            throw new \InvalidArgumentException(
                sprintf(
                    '%s is not a valid email address',
                    $email
                )
            );
        $this->carbonCopy .= (strlen($this->carbonCopy) ? ',' : '').$email;
        return $this;
    }

    /**
     * @return string
     */
    public function getCarbonCopy()
    {
        return $this->carbonCopy;
    }

    /**
     * @param array $fullArray
     */
    public function setFullArray($fullArray)
    {
        $this->fullArray = $fullArray;
    }

    /**
     * @return array
     */
    public function getFullArray()
    {
        return $this->fullArray;
    }

    /**
     * @param array $minArray
     */
    public function setMinArray($minArray)
    {
        $this->minArray = $minArray;
    }

    /**
     * @return array
     */
    public function getMinArray()
    {
        return $this->minArray;
    }

    /**
     * @param null $stringId
     */
    public function setStringId($stringId)
    {
        $this->stringId = $stringId;
    }

    /**
     * @return null
     */
    public function getStringId()
    {
        return $this->stringId;
    }

    /**
     * @param string $subscriptionNotify
     */
    public function setSubscriptionNotify($subscriptionNotify)
    {
        $this->subscriptionNotify = $subscriptionNotify;
    }

    /**
     * @return string
     */
    public function getSubscriptionNotify()
    {
        return $this->subscriptionNotify;
    }

    /**
     * @param string $toName
     */
    public function setToName($toName)
    {
        $this->toName = $toName;
    }

    /**
     * @return string
     */
    public function getToName()
    {
        return $this->toName;
    }

    /**
     * @param string $unsubscriptionNotify
     */
    public function setUnsubscriptionNotify($unsubscriptionNotify)
    {
        $this->unsubscriptionNotify = $unsubscriptionNotify;
    }

    /**
     * @return string
     */
    public function getUnsubscriptionNotify()
    {
        return $this->unsubscriptionNotify;
    }

    /**
     * @param null $name
     */
    public function setName($name)
    {
        $this->name = (string) $name;
        return $this;
    }

    /**
     * @return null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param null $senderAddr1
     */
    public function setSenderAddr1($senderAddr1)
    {
        $this->senderAddr1 = (string) $senderAddr1;
        return $this;
    }

    /**
     * @return null
     */
    public function getSenderAddr1()
    {
        return $this->senderAddr1;
    }

    /**
     * @param null $senderCity
     */
    public function setSenderCity($senderCity)
    {
        $this->senderCity = (string) $senderCity;
        return $this;
    }

    /**
     * @return null
     */
    public function getSenderCity()
    {
        return $this->senderCity;
    }

    /**
     * @param null $senderCountry
     */
    public function setSenderCountry($senderCountry)
    {
        $this->senderCountry = (string) $senderCountry;
        return $this;
    }

    /**
     * @return null
     */
    public function getSenderCountry()
    {
        return $this->senderCountry;
    }

    /**
     * @param null $senderName
     */
    public function setSenderName($senderName)
    {
        $this->senderName = (string) $senderName;
        return $this;
    }

    /**
     * @return null
     */
    public function getSenderName()
    {
        return $this->senderName;
    }

    /**
     * @param null $senderZip
     */
    public function setSenderZip($senderZip)
    {
        $this->senderZip = $senderZip;
        return $this;
    }

    /**
     * @return null
     */
    public function getSenderZip()
    {
        return $this->senderZip;
    }

}