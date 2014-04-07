<?php
namespace AC\Arguments;
use AC\Models\Base;


class Action
{

    const ACTION_GET = 'GET';
    const ACTION_POST = 'POST';
    const ACTION_PUT = 'PUT';
    const ACTION_DELETE = 'DELETE';

    const API_DEFAULT_ACTION = 'api_action';

    protected $action = self::API_DEFAULT_ACTION;
    protected $mode = Config::HOSTED_MODE;
    protected $method = '';
    protected $output = 'json';
    protected $verb = self::ACTION_GET;
    protected $params = null;
    protected $data = '';
    protected $stringBase = null;

    protected $full = false;

    public function __construct(array $set = array(), Config $conf = null)
    {
        if ($conf)
        {//set defaults
            $this->output = $conf->getOutput();
            $this->mode = $conf->getMode();
        }
        foreach ($set as $k => $v)
        {//allow overrides
            $k = 'set'.ucfirst($k);
            if (method_exists($this, $k))
            {
                $this->{$k}($v);
            }
        }
    }

    public function resetAction(array $params)
    {
        foreach ($params as $k => $v)
        {
            $k = 'set'.ucfirst($k);
            if (method_exists($this, $k))
            {
                $this->{$k}($v);
            }
        }
        return $this;
    }

    public function setFullData($on = true)
    {
        $this->full =  (bool) $on;
        return $this;
    }

    public function setData($mixed)
    {
        if ($mixed instanceof Base)
        {
            $mixed = $mixed->toArray(
                $this->full
            );
        }
        if ($mixed)
        {
            if ($this->verb !== self::ACTION_PUT)
            {
                $this->verb = self::ACTION_POST;
            }
        }
        $this->data = $mixed;
        return $this;
    }

    public function getData($stringified = false)
    {
        if ($stringified === false || $this->data === '')
        {
            return $this->data;
        }
        $tmpData = $this->data;
        if ($tmpData instanceof Base)
        {
            $tmpData = $tmpData->toArray($this->full);
        }
        $data = array();
        foreach ($tmpData as $key => $value)
        {
            if (!is_array($value))
            {
                $data[] = $key .'='.urlencode($value);
            }
            else
            {
                if (is_int($key))
                {
                    foreach ($value as $key_ => $value_)
                    {
                        if (is_array($value_))
                        {
                            foreach ($value_ as $k => $v)
                            {
                                $data[] = sprintf(
                                    '%s[%d][%s]=%s',
                                    $key_,
                                    $key,
                                    urlencode($k),
                                    urlencode($v)
                                );
                            }
                        }
                        else
                        {
                            $data[] = $key_.'['.$key.']='.urlencode($value_);
                        }
                    }
                }
                else
                {
                    foreach ($value as $k => $v)
                    {
                        $data[] = sprintf(
                            '%s[%d]=%s',
                            $key,
                            $k,
                            urlencode($v)
                        );
                    }
                }
            }
        }
        return implode('&', $data);
    }

    public function setVerb($action)
    {
        if ($this->data !== null && $this->verb === self::ACTION_POST)
        {//force GET, add data to method as string
            $this->stringBase = null;
            $this->method .= '&'.$this->getData(true);
            $this->data = null;
        }
        $this->verb = $action;
        return $this;
    }

    public function getVerb()
    {
        return $this->verb;
    }

    public function setParams($mixed)
    {
        if ($this->stringBase)
            $this->stringBase = null;
        if (is_array($mixed))
        {
            $str = '';
            foreach ($mixed as $name => $val)
            {
                if (strstr($val, '=') === false)
                {
                    $str .= '&'.$name.'='.$val;
                }
                else
                {
                    $str .= $val{0} == '&' ? $val : ('&'.$val);
                }
            }
            $mixed = $str;
        }
        if ($mixed{0} !== '&')
        {
            $mixed = '&'.$mixed;
        }
        $this->params = $mixed;
        return $this;
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function setOutput($out)
    {
        $this->stringBase = null;
        $this->output = $out;
        return $this;
    }

    public function getParams($asArray = false)
    {
        if (!$asArray)
        {
            return $this->params;
        }
        if (preg_match_all('/&([^=]+)=([^&]+)/',$this->params, $matches))
        {
            return array_combine($matches[1], $matches[2]);
        }
        return array();
    }

    public function setMethod($method)
    {
        $find = array('&', '=');
        $replace = array('','');
        if ($this->mode === Config::ONSITE_MODE)
        {
            $find[] = 'contact';
            $replace[] = 'subscriber';
        }
        $this->stringBase = null;
        $this->method = str_replace(
            $find,
            $replace,
            $method
        );
        return $this;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setAction($action)
    {
        $this->stringBase = null;
        $this->action = str_replace(
            array(
                '&',
                '='
            ),
            '',
            $action
        );
        return $this;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function __toString()
    {
        if ($this->stringBase === null)
        {
            $this->stringBase = sprintf(
                '&%s=%s&api_output=%s',
                $this->action,
                $this->method,
                $this->output
            );
            $this->stringBase .= $this->params;
        }
        return sprintf(
            '%s%s',
            $this->stringBase,
            $this->getParams()
        );
    }
} 
