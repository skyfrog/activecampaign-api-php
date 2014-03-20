<?php
namespace AC;

use AC\Arguments\Config,
    AC\Arguments\Action;

class Connector
{
    private $debug = false;
    protected $config = null;
    protected $url = null;
    protected $output;
    protected $dbBuffer = array();

    private static $StringOnly = array(
        'form_html',
        'tracking_site_status',
        'tracking_event_status',
        'tracking_whitelist',
        'tracking_log',
        'tracking_site_list',
        'tracking_event_list'
    );

    function __construct(Config $conf = null, $debug = false)
    {
        $this->config = $conf;
        if ($conf instanceof Config)
        {
            $this->output = $conf->getOutput();
            $this->url = $conf->getUrl();
        }
        else
            $this->output = 'json';
        $this->debug = $debug;
    }

    public static function postUpdate($event)
    {
        $io = $event->getIO();
        do
        {
            $r = $io->ask('Set ACTIVECAMPAIGN_* constants now? [y/N]', 'N');
            switch (strtoupper($r))
            {
                case 'Y':
                    $contents = '<?php%sdefine("ACTIVECAMPAIGN_URL", "%s");%sdefine("ACTIVECAMPAIGN_API_KEY", "%s");%s';
                    $url = $io->ask('Value for ACTIVECAMPAIGN_URL: ', '');
                    $key = $io->ask('Value for ACTIVECAMPAIGN_API_KEY: ', '');
                    file_put_contents(
                        dirname(__FILE__).'/config.php',
                        sprintf(
                            $contents,
                            PHP_EOL,
                            $url,
                            PHP_EOL,
                            $key,
                            PHP_EOL
                        )
                    );
                case 'N':
                    $r = null;
                    break;
                default:
                    $io->overwrite($r. ' is not a valid option, either enter y or n');
            }
        } while($r !== null);
    }

    public function __get($name)
    {
        $method = 'get'.ucfirst($name);
        if (method_exists($this->config, $method))
        {
            return $this->config->{$method}();
        }
        //final try
        $method = 'get'.implode(
            '',
            array_map(
                'ucfirst',
                explode(
                    '_',
                    strtolower($name)
                )
            )
        );
        if (method_exists($this->config, $method))
        {
                return $this->config->{$method}();
        }
        throw new \RuntimeException(
            sprintf(
                'Property %s not found',
                $name
            )
        );
    }

    public function __call($method, array $args)
    {
        $rename = explode('_', $method);
        for ($i=1,$j=count($rename);$i<$j;++$i)
            $rename[$i] = ucfirst($rename[$i]);
        $rename = implode('', $rename);
        if (method_exists($this, $rename))
            return call_user_func_array(
                array(
                    $this,
                    $rename
                ),
                $args
            );
        throw new \BadMethodCallException('Method '.$method.' does not exist');
    }

    public function __set($name, $val)
    {
        if (property_exists($this, $name))
        {
            return ($this->{$name} = $val);
        }
        $method = 'set'.implode(
            '',
            array_map(
                'ucfirst',
                explode(
                    '_',
                    strtolower($name)
                )
            )
        );
        if (method_exists($this->config, $method))
        {
            $this->config->{$method}($val);
            return $val;
        }
        throw new \RuntimeException(
            sprintf(
                'Property %s not found, config setter %s does not exist...',
                $name,
                $method
            )
        );
    }

    public function enableDebug()
    {
        $this->debug = true;
        return $this;
    }

    public function toggleDebug()
    {
        $this->debug = !$this->debug;
    }

    public function isDebugEnabled($disable = false)
    {
        if ($this->debug === true && $disable === true)
        {
            $this->debug = false;
        }
        return $this->debug;
    }

    protected function getUrl()
    {
        if ($this->url === null)
        {
            $this->url = $this->config->composeUrl();
        }
        return $this->url;
    }

    protected function getApiKey()
    {
        return $this->config->getApiKey();
    }

    public function credentialsTest()
    {
        $action = new Action(
            array(
                'method'    => 'user_me',
                'output'    => $this->output
            ),
            $this->config
        );
        $this->doAction($action);
        return true;
        $testUrl = $this->getUrl().'&api_action=user_me&api_output='.$this->output;
        $r = $this->curl($testUrl);
        if (is_object($r) && (int)$r->result_code)
        {
            // successful
            return true;
        }
        return false;
    }

    /**
     * Initial documentation/comment for this function read:
     * // debug function (nicely outputs variables)
     * WTR?! NICELY??? This is a connector class, serving as base-class
     * for the entire API, and it ECHOES, calls EXIT... what's next? or die?
     * @todo: REFACTOR THIS C***
     * @param $var
     * @param int $continue
     * @param string $element
     * @param string $extra
     */
    public function dbg($var, $continue = 0, $element = "pre", $extra = "")
    {
        $len = '';
        if (is_array($var))
            $len = 'Elements: '.count($var);
        if (is_string($var))
            $len = 'Length: '. strlen($var);
        $extra = $extra ? $extra : '';
        echo '<' , $element , '>Vartype: ',
            gettype($var), PHP_EOL,
            $len, PHP_EOL, $extra, PHP_EOL;
        print_r($var);
        echo '</', $element, '>';
        if (!$continue) exit();
    }

    /**
     * Alternative, more OO take on this object's curl method
     * basically a copy-paste-rewrite ;)
     * @param Action $todo
     * @param null $customMethod
     * @return mixed
     * @throws \RuntimeException
     */
    public function doAction(Action $todo, $customMethod = null)
    {
        $method = $customMethod;
        if ($customMethod === null)
        {
            $method = $todo->getMethod();
        }
        $url = $this->getUrl(). (string) $todo;
        $debug_str1 = array();
        $request = curl_init();
        $debug_str1[] = '$ch = curl_init();';
        if ($this->debug)
        {
            $this->dbg($url, 1, "pre", "Description: Request URL");
        }
        curl_setopt($request, \CURLOPT_URL, $url);
        curl_setopt($request, \CURLOPT_HEADER, 0);
        curl_setopt($request, \CURLOPT_RETURNTRANSFER, true);
        $debug_str1[] = 'curl_setopt($ch, \CURLOPT_URL,"'.$url.'");';
        $debug_str1[] = 'curl_setopt($ch, \CURLOPT_HEADER,0);';
        $debug_str1[] = 'curl_setopt($ch, \CURLOPT_RETURNTRANSFER,true);';
        $data = $todo->getData();
        if ($todo->getVerb() !== 'GET' && $data)
        {
            switch ($todo->getVerb())
            {
                case 'PUT':
                    curl_setopt($request, \CURLOPT_CUSTOMREQUEST, 'PUT');
                    $debug_str1[] = 'curl_setopt($request, CURLOPT_CUSTOMREQUEST, "PUT")';
                    break;
                case 'POST':
                    curl_setopt($request, \CURLOPT_POST, 1);
                    $debug_str1[] = 'curl_setopt($ch, CURLOPT_POST, 1);';
                    break;
                default:
                    throw new \RuntimeException(
                        sprintf(
                            'Request WITH data implies PUT or POST verb in action, %s given',
                            $todo->getVerb()
                        )
                    );

            }
            curl_setopt($request, \CURLOPT_HTTPHEADER, array('Expect:'));
            $debug_str1[] = 'curl_setopt($request, \CURLOPT_HTTPHEADER, array("Expect:"));';
            if ($this->debug)
            {
                $this->dbg($todo->getData(true), 1, "pre", "Description: POST data");
            }
            curl_setopt($request, \CURLOPT_POSTFIELDS, $data);
            $debug_str1[] = 'curl_setopt($request, CURLOPT_POSTFIELDS, $data);';
        }
        curl_setopt($request, \CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($request, \CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($request, \CURLOPT_FOLLOWLOCATION, true);
        $debug_str1[] = 'curl_setopt($ch, \CURLOPT_SSL_VERIFYPEER, false);';
        $debug_str1[] = 'curl_setopt($ch, \CURLOPT_SSL_VERIFYHOST, 0);';
        $debug_str1[] = 'curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, true);';
        $response = curl_exec($request);
        $debug_str1[] = 'curl_exec($ch);';
        if ($this->debug)
        {
            $this->dbg($response, 1, "pre", "Description: Raw response");
        }
        $httpCode = curl_getinfo($request, \CURLINFO_HTTP_CODE);
        $debug_str1[] = '$httpCode = curl_getinfo($ch, \CURLINFO_HTTP_CODE);';
        if ($this->debug)
        {
            $this->dbg($httpCode, 1, "pre", "Description: Response HTTP code");
        }
        curl_close($request);
        $debug_str1[] = 'curl_close($ch);';
        $object = json_decode($response);
        if ($this->debug)
        {
            $this->dbg($object, 1, "pre", "Description: Response object (json_decode)");
        }
        $jError = json_last_error();
        if ($jError !== \JSON_ERROR_NONE)
        {
            // add methods that only return a string
            if (in_array($method, self::$StringOnly))
            {
                return $response;
            }
            throw new \RuntimeException(
                sprintf(
                    'An unexpected problem occurred with the API request. Some causes include: invalid JSON or XML returned.
                    Here is the actual response from the server: ---- %s%s%s
                    JSON errors: %d - %s',
                    PHP_EOL.'<br>',
                    $response,
                    PHP_EOL.'<br>',
                    $jError,
                    $this->getJSONError($jError)
                )
            );
        }
        if ($this->debug)
        {
            $debug_str1 = implode(PHP_EOL, $debug_str1);
            //if needs must, do not echo, we're setting headers below!!
            $this->dbBuffer[] = "<textarea style='height: 300px; width: 600px;'>" . $debug_str1 . "</textarea>";
        }

        header("HTTP/1.1 " . $httpCode);
        $object->http_code = $httpCode;

        if (isset($object->result_code))
        {
            $object->success = $object->result_code;
            if (!(int)$object->result_code)
            {
                $object->error = $object->result_message;
            }
        }
        elseif (isset($object->succeeded))
        {
            // some calls return "succeeded" only
            $object->success = $object->succeeded;
            if (!(int)$object->succeeded)
            {
                $object->error = $object->message;
            }
        }
        return $object;
    }

    /**
     * Backwards compatible version of json_last_error_msg
     * based on http://www.php.net/json_last_error_msg#113243
     * @param int $code
     * @return string
     */
    protected function getJSONError($code)
    {
        if (function_exists('json_last_error_msg'))
        {
            return json_last_error_msg();
        }
        switch($code)
        {
            case \JSON_ERROR_NONE:
                return '';
            case \JSON_ERROR_DEPTH:
                return 'Maximum stack depth exceeded';
            case \JSON_ERROR_STATE_MISMATCH:
                return 'Underflow or the modes mismatch';
            case \JSON_ERROR_CTRL_CHAR:
                return 'Unexpected control character found';
            case \JSON_ERROR_SYNTAX:
                return 'Syntax error, malformed JSON';
            case \JSON_ERROR_UTF8:
                return 'Malformed UTF-8 characters, possibly incorrectly encoded';
            default:
                return 'Unknown error code '.$code;
        }
    }

    public function curl($url, $post_data = array(), $verb = "GET", $custom_method = "")
    {
        if ($this->version == 1)
        {
            // find the method from the URL.
            $method = preg_match("/api_action=[^&]*/i", $url, $matches);
            if ($matches)
            {
                $method = preg_match("/[^=]*$/i", $matches[0], $matches2);
                $method = $matches2[0];
            } elseif ($custom_method)
            {
                $method = $custom_method;
            }
        } elseif ($this->version == 2)
        {
            $method = $custom_method;
            $url .= "?api_key=" . $this->api_key;
        }
        $debug_str1 = "";
        $request = curl_init();
        $debug_str1 .= "\$ch = curl_init();\n";
        if ($this->debug)
        {
            $this->dbg($url, 1, "pre", "Description: Request URL");
        }
        curl_setopt($request, CURLOPT_URL, $url);
        curl_setopt($request, CURLOPT_HEADER, 0);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        $debug_str1 .= "curl_setopt(\$ch, CURLOPT_URL, \"" . $url . "\");\n";
        $debug_str1 .= "curl_setopt(\$ch, CURLOPT_HEADER, 0);\n";
        $debug_str1 .= "curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);\n";
        if ($post_data)
        {
            if ($verb == "PUT")
            {
                curl_setopt($request, CURLOPT_CUSTOMREQUEST, "PUT");
                $debug_str1 .= "curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, \"PUT\");\n";
            } else
            {
                $verb = "POST";
                curl_setopt($request, CURLOPT_POST, 1);
                $debug_str1 .= "curl_setopt(\$ch, CURLOPT_POST, 1);\n";
            }
            $data = "";
            if (is_array($post_data))
            {
                foreach ($post_data as $key => $value)
                {
                    if (is_array($value))
                    {

                        if (is_int($key))
                        {
                            // array two levels deep
                            foreach ($value as $key_ => $value_)
                            {
                                if (is_array($value_))
                                {
                                    foreach ($value_ as $k => $v)
                                    {
                                        $k = urlencode($k);
                                        $data .= "{$key_}[{$key}][{$k}]=" . urlencode($v) . "&";
                                    }
                                } else
                                {
                                    $data .= "{$key_}[{$key}]=" . urlencode($value_) . "&";
                                }
                            }
                        } else
                        {
                            // IE: [group] => array(2 => 2, 3 => 3)
                            // normally we just want the key to be a string, IE: ["group[2]"] => 2
                            // but we want to allow passing both formats
                            foreach ($value as $k => $v)
                            {
                                if (!is_array($v))
                                {
                                    $k = urlencode($k);
                                    $data .= "{$key}[{$k}]=" . urlencode($v) . "&";
                                }
                            }
                        }

                    } else
                    {
                        $data .= "{$key}=" . urlencode($value) . "&";
                    }
                }
            } else
            {
                // not an array - perhaps serialized or JSON string?
                // just pass it as data
                $data = "data={$post_data}";
            }

            $data = rtrim($data, "& ");
            curl_setopt($request, CURLOPT_HTTPHEADER, array("Expect:"));
            $debug_str1 .= "curl_setopt(\$ch, CURLOPT_HTTPHEADER, array(\"Expect:\"));\n";
            if ($this->debug)
            {
                $this->dbg($data, 1, "pre", "Description: POST data");
            }
            curl_setopt($request, CURLOPT_POSTFIELDS, $data);
            $debug_str1 .= "curl_setopt(\$ch, CURLOPT_POSTFIELDS, \"" . $data . "\");\n";
        }
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($request, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($request, CURLOPT_FOLLOWLOCATION, true);
        $debug_str1 .= "curl_setopt(\$ch, CURLOPT_SSL_VERIFYPEER, false);\n";
        $debug_str1 .= "curl_setopt(\$ch, CURLOPT_SSL_VERIFYHOST, 0);\n";
        $debug_str1 .= "curl_setopt(\$ch, CURLOPT_FOLLOWLOCATION, true);\n";
        $response = curl_exec($request);
        $debug_str1 .= "curl_exec(\$ch);\n";
        if ($this->debug)
        {
            $this->dbg($response, 1, "pre", "Description: Raw response");
        }
        $http_code = curl_getinfo($request, CURLINFO_HTTP_CODE);
        $debug_str1 .= "\$http_code = curl_getinfo(\$ch, CURLINFO_HTTP_CODE);\n";
        if ($this->debug)
        {
            $this->dbg($http_code, 1, "pre", "Description: Response HTTP code");
        }
        curl_close($request);
        $debug_str1 .= "curl_close(\$ch);\n";
        $object = json_decode($response);
        if ($this->debug)
        {
            $this->dbg($object, 1, "pre", "Description: Response object (json_decode)");
        }
        if (!is_object($object) || (!isset($object->result_code) && !isset($object->succeeded) && !isset($object->success)))
        {
            // add methods that only return a string
            $string_responses = array("form_html", "tracking_site_status", "tracking_event_status", "tracking_whitelist", "tracking_log", "tracking_site_list", "tracking_event_list");
            if (in_array($method, $string_responses))
            {
                return $response;
            }
            // something went wrong
            return "An unexpected problem occurred with the API request. Some causes include: invalid JSON or XML returned. Here is the actual response from the server: ---- " . $response;
        }

        if ($this->debug)
        {
            echo "<textarea style='height: 300px; width: 600px;'>" . $debug_str1 . "</textarea>";
        }

        header("HTTP/1.1 " . $http_code);
        $object->http_code = $http_code;

        if (isset($object->result_code))
        {
            $object->success = $object->result_code;
            if (!(int)$object->result_code)
            {
                $object->error = $object->result_message;
            }
        } elseif (isset($object->succeeded))
        {
            // some calls return "succeeded" only
            $object->success = $object->succeeded;
            if (!(int)$object->succeeded)
            {
                $object->error = $object->message;
            }
        }
        return $object;
    }

}
