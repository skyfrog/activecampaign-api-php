<?php
namespace AC\Arguments;


class Config
{
    protected $version = 1;
    protected $urlBase = '';
    protected $url;
    protected $apiKey = null;
    protected $output = 'json';

    private $apiUser = '';
    private $apiPass = '';

    public function __construct(array $conf = array())
    {
        foreach ($conf as $name => $value)
        {
            $name =  'set'.ucfirst($name);
            if (method_exists($this, $name))
            {
                $this->{$name}($value);
            }
        }
        //make sure the url is set-up properly
        $this->composeUrl();
    }

    public function composeUrl($url = null, $base = null, $key = null, array $userPass = null, $isHashed = true)
    {
        $url = $url ? $url : $this->getUrl();
        if (strstr($url,'/api.php?'))
        {
            return $this->setUrl($url)->getUrl();
        }
        $base = $base ? $base : $this->getUrlBase();
        if (is_array($key))
        {
            $isHashed = $userPass !== null ? $userPass : $isHashed;
            $userPass = $key;
            $key = null;
        }
        else
        {
            $key = $key ? $key : $this->getApiKey();
        }
        if (!strstr($url, 'https://www.activecampaign.com/'))
        {
            $base = '/admin';
        }
        if (substr($url, -1) === '/')
        {
            $url = substr($url, 0, -1);
        }
        $this->setUrlBase($base);
        $url .= $base . '/api.php?api_';
        if ($key)
        {
            return $this->setApiKey($key)
                ->setUrl($url.'key='.$key)
                ->getUrl();
        }
        if ($userPass === null)
        {
            $userPass = array(
                'user'  => $this->getApiUser(),
                'pass'  => $this->getApiPass()
            );
        }
        elseif (!$isHashed)
        {//hash
            $userPass['pass'] = md5($userPass['pass']);
        }
        return $this->setUrl(
            $url.'user='.$userPass['user'].'&api_pass='.$userPass['pass']
        )->setApiPass($userPass['pass'])
        ->setApiUser($userPass['user'])
        ->getUrl();
    }

    /**
     * @param mixed $api_key
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = (string) $apiKey;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function setOutput($out)
    {
        $this->output = (string) $out;
        return $this;
    }

    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = (string) $url;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url_base
     */
    public function setUrlBase($urlBase)
    {
        $this->urlBase = (string) $urlBase;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUrlBase()
    {
        return $this->urlBase;
    }

    /**
     * @param mixed $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    public function setApiUser($user)
    {
        $this->apiUser = $user;
        return $this;
    }

    public function getApiUser()
    {
        return $this->apiUser;
    }

    public function setApiPass($pass)
    {
        if (strlen($pass) !== 32 || preg_match('/[^a-f0-9]/', $pass))
        {//last resort: guess pass was not hashed yet
            $pass = md5($pass);
        }
        $this->apiPass = $pass;
        return $this;
    }

    public function getApiPass(Connector $conn = null)
    {
        if ($conn === null)
        {//avoid getting pass in the wrong places
            throw new \BadMethodCallException(
                'Getting the pass requires passing of a valid connection'
            );
        }
        return $this->apiPass;
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->version;
    }

} 