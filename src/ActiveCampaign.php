<?php
namespace AC;

use AC\Arguments\Config;

class ActiveCampaign extends Connector
{

    public $version = 1;
    public $debug = false;

    function __construct(Config $conf, $debug = false)
    {
        parent::__construct($conf, $debug);
    }

    function version($version)
    {
        $this->version = (int)$version;
        if ($version == 2)
        {
            $this->url_base = $this->url_base . "/2";
        }
    }

    function api($path, $post_data = array())
    {
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
            case 'list':
                $component = 'cList';
                break;
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
