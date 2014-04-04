<?php
namespace AC;

use AC\Arguments\Config,
    AC\Arguments\Action;

class ActiveCampaign extends Connector
{

    const API_SECTION_ACCOUNT = 0;
    const API_SECTION_AUTH = 1;
    const API_SECTION_CAMPAIGN = 2;
    const API_SECTION_LIST = 3;
    const API_SECTION_CONTACT = 4;
    const API_SECTION_DESIGN = 5;
    const API_SECTION_FORM = 6;
    const API_SECTION_GROUP = 7;
    const API_SECTION_MESSAGE = 8;
    const API_SECTION_TRACKING = 9;
    const API_SECTION_USER = 10;
    const API_SECTION_WEBHOOK = 11;

    private $apiSections = array(
        'Account',
        'Auth',
        'Campaign',
        'CList',
        'Contact',
        'Design',
        'Form',
        'Group',
        'Message',
        'Tracking',
        'User',
        'Webhook'
    );

    public $version = 1;
    public $debug = false;
    public $cacheAction = true;
    public $actionCache = array();

    function __construct(Config $conf, $cacheAction = true, $debug = false)
    {
        $this->cacheAction = (bool) $cacheAction;
        parent::__construct($conf, $debug);
    }

    public function version($version)
    {
        if ($this->version === 2)
        {//remove trailing /2 that we have set because we're in version 2-mode
            $this->config->setUrlBase(
                substr(
                    $this->config->getUrlBase(),
                    0,
                    -2
                )
            );
        }
        $this->version = (int)$version;
        if ($version == 2)
        {
            $this->config->setUrlBase(
                $this->config->getUrlBase().'/2'
            );
        }
    }

    /**
     * @param int $section
     * @param null $debug
     * @return \AC\Connector
     * @throws \InvalidArgumentException
     */
    public function getApiSection($section, $debug = null)
    {
        if (!isset($this->apiSections[$section]))
        {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s is not a valid section, use constants',
                    "$section"
                )
            );
        }
        if ($this->apiSections[$section] instanceof ActiveCampaign)
            return $this->apiSections[$section];
        $className = __NAMESPACE__.'\\'.$this->apiSections[$section];
        if ($debug ===  null)
            $debug = $this->debug;
        $this->apiSections[$section] = new $className(
            $this->config,
            $debug
        );
        return $this->apiSections[$section];
    }

    public function directAction(Action $a)
    {
        return $this->doAction($a);
    }

    public function api($path, $post_data = array())
    {
        $action = null;
        if ($this->cacheAction && isset($this->actionCache[$path]))
        {
                $action = $this->actionCache[$path];
        }
        // IE: "contact/view"
        $components = explode("/", $path);
        $component = $components[0];

        if (count($components) > 2)
        {
            // IE: "contact/tag/add?whatever"
            // shift off the first item (the component, IE: "contact").
            array_shift($components);
            // IE: convert to "tag_add?whatever"
            $method_str = implode("_", $components);
            $components = array($component, $method_str);
        }

        if (preg_match("/\?/", $components[1]))
        {
            // query params appended to method
            // IE: contact/edit?overwrite=0
            $method_arr = explode("?", $components[1]);
            $method = $method_arr[0];
            $params = $method_arr[1];
        } else
        {
            // just a method provided
            // IE: "contact/view
            if (isset($components[1]))
            {
                $method = $components[1];
                $params = "";
            } else
            {
                return "Invalid method.";
            }
        }

        // adjustments
        $add_tracking = false;
        switch ($component)
        {
            //case 'list':
                //$component = 'cList';
                //break;
            case 'branding':
                $component = 'design';
                break;
            case 'sync':
                $method = $component;
                $component = 'design';
                break;
            case 'singlesignon':
                $component = 'auth';
                break;
            case 'tracking':
                $add_tracking = true;
                break;
        }
        if ($action === null)
        {
            $action = new Action(
                array(
                    'method' => $component.'_'.$method,
                    'output' => $this->output
                ),
                $this->config
            );
            if ($this->cacheAction === true)
                $this->actionCache[$path] = $action;
        }
        else
        {
            $action->setOutput($this->output);
        }
        if ($post_data)
        {
            $action->setData($post_data);
        }
        if ($params)
        {
            $action->setParams($params);
        }
        return $this->doAction($action);
        //leave original code here, as a todo list
        $class = ucwords($component); // IE: "contact" becomes "Contact"
        $class = __NAMESPACE__ .'\\' . $class;

        $class = new $class($this->config, $this->debug);
        // IE: $contact->view()

        if ($add_tracking)
        {
            $class->track_email = $this->track_email;
            $class->track_actid = $this->track_actid;
            $class->track_key = $this->track_key;
        }

        if ($method == "list")
        {
            // reserved word
            $method = "list_";
        }

        $class->debug = $this->debug;

        $response = $class->$method($params, $post_data);
        return $response;
    }

}
