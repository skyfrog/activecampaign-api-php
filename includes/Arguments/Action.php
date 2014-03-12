<?php
namespace AC\Arguments;


class Action
{

    const ACTION_GET = 'GET';
    const ACTION_POST = 'POST';
    const ACTION_PUT = 'PUT';
    const ACTION_DELETE = 'DELETE';

    const API_DEFAULT_ACTION = 'api_action';

    protected $action = self::API_DEFAULT_ACTION;
    protected $method = '';
    protected $output = 'json';
    protected $verb = self::ACTION_GET;
    protected $data = '';

    public function __construct(array $set = array(), Config $conf = null)
    {
        foreach ($set as $k => $v)
        {
            $k = 'set'.ucfirst($k);
            if (method_exists($this, $k))
            {
                $this->{$k}($v);
            }
        }
        if ($conf)
        {
            $this->output = $conf->getOutput();
        }
    }

    public function setData($mixed)
    {
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
        if ($stringified === false || $this->data = '')
        {
            return $this->data;
        }
        $data = '';
        foreach ($this->data as $key => $value)
        {
            if (!is_array($value))
            {
                $data .= $key."=" . urlencode($value) . "&";
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
                                $data .= sprintf(
                                    '%s[%d][%s]=%s&',
                                    $key_,
                                    $key,
                                    urlencode($k),
                                    urlencode($v)
                                );
                            }
                        }
                        else
                        {
                            $data .= $key_.'['.$key.']='.urlencode($value_).'&';
                        }
                    }
                }
                else
                {
                    // IE: [group] => array(2 => 2, 3 => 3)
                    // normally we just want the key to be a string, IE: ["group[2]"] => 2
                    // but we want to allow passing both formats
                    foreach ($value as $k => $v)
                    {
                        if (!is_array($v))
                        {
                            $k = urlencode($k);
                            $data .= sprintf(
                                '%s[%d]=%s&',
                                $key,
                                urlencode($k),
                                urlencode($v)
                            );
                        }
                    }
                }

            }
        }
        //remove trailing &
        return substr($data, 0, -1);
    }

    public function setVerb($action)
    {
        $this->verb = $action;
        return $this;
    }

    public function getVerb()
    {
        return $this->verb;
    }

    public function setOutput($out)
    {
        $this->output = $out;
        return $this;
    }

    public function setMethod($method)
    {
        $this->method = str_replace(
            array(
                '&',
                '='
            ),
            '',
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
        return sprintf(
            '&%s=%s&api_output=%s',
            $this->action,
            $this->method,
            $this->output
        );
    }
} 