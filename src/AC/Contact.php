<?php
namespace AC;

class Contact extends ActiveCampaign
{

    protected $statusBuffer = false;
    protected $lastPage = array();
    protected $statuses = array();
    protected $paginator = array(
        'default'   => array(
            'offset'    => 0,
            'limit'     => 50
        )
    );

    public function setBufferStatus($buffer = false)
    {
        if (!$buffer)
            $this->statuses = array();//clear current buffer, if exists
        $this->statusBuffer = $buffer;
        return $this;
    }

    public function getContactsByEmail(array $contact, $method = null)
    {
        $return = array();
        $call = null;
        if ($method)
        {
            if (method_exists($contact[0], $method))
                $call = true;
            elseif (property_exists($contact[0], $method))
                $call = false;
        }
        foreach ($contact as $c)
        {
            try
            {
                if ($call !== null)
                    $c = $call ? $c->{$method}() : $c->{$method};
                $resp = $this->getContactByEmail($c);
                $return[$resp->email] = $resp;
            }
            catch(\Exception $e)
            {
                if ($call !== null)
                    $return[$c] = $e;
                else
                    $return[serialize($c)] = $e;
            }
        }
        return $return;
    }

    public function getContactByEmail($contact)
    {
        if (is_object($contact))
        {
            if (method_exists($contact, 'getEmail'))
                $contact = $contact->getEmail();
            else if (property_exists($contact, 'email'))
                $contact = $contact->email;
            else
                throw new \InvalidArgumentException(
                    'Supplied argument does not have email getter or property'
                );
        }
        $action = $this->getAction(
            __METHOD__,
            array(
                'method'    => 'contact_view_email',
                'data'      => array(
                    'email' => (string) $contact
                )
            )
        );
        $resp = $this->doAction($action);
        if ($resp->result_code == 0)
            throw new \RuntimeException(
                sprintf(
                    '%s call failed: %s (request url: %s)',
                    $action->getAction(),
                    $resp->response_message,
                    (string) $action
                )
            );
        if ($this->statusBuffer)
            $this->statuses[$resp->id] = $resp->status;
        return $resp;
    }

    public function getContactStatus($contact, $default = 0)
    {
        $contact = (string) $contact;
        if (!isset($this->statuses[$contact]))
        {
            $this->getContacts(array($contact));
            if (!isset($this->statuses[$contact]))
                return (int) $default;
        }
        return (int) $this->statuses[$contact];
    }

    public function getContactStatusses(array $contacts, $default = 0)
    {
        $return = array();
        if ($this->statusBuffer === true)
        {
            if (empty($this->statuses))
                $this->getContacts($contacts, 1);
            //complete return array by setting default/missing ids to passed $default value
            foreach ($contacts as $contact)
                $return[(string) $contact] = $this->getContactStatus($contact, $default);
            return $return;
        }
        $pages = $this->getContacts($contacts, true, 1);//get all contacts
        $pool = array();
        for ($i=0;property_exists($pages, (string) $i);++$i)
        {
            $return[$pages->{$i}->id] = $pages->{$i}->status;
        }
        for ($i=0, $max = count($contacts);$i<$max;++$i)
        {
            if (!isset($pool[(string) $contacts[$i]]))
                $pool[(string) $contacts[$i]] = $default;
        }
        return $pool;
    }

    public function getContacts(array $contacts, $full = false, $page = 1)
    {
        if (count($contacts) > 20)
        {
            $calls = array_chunk($contacts, 20);
            $return = array();
            foreach ($calls as $set)
            {
                $return[$page] = $this->getContacts($set, $full, $page++);
            }
            $return['pages'] = $page-1;
            return $return;
        }
        foreach ($contacts as $k => $c)
            $contacts[$k] = (string) $c;//ensure we have strings
        $action = $this->getAction(
            __METHOD__,
            array(
                'method'    => 'contact_list',
                'data'      => array(
                    'ids'   => implode(',', $contacts),
                    'page'  => $page,
                    'full'  => $full ? 1 : 0
                )
            )
        );
        $resp = $this->doAction(
            $action
        );
        if ($resp->result_code == 0 && $resp->http_code != 200)
        {
            throw new \RuntimeException(
                sprintf(
                    '%s call failed: %s on page %d.(request url: %s)',
                    $action->getAction(),
                    $resp->result_message,
                    (int) $page,
                    (string) $action
                )
            );
        }
        if ($this->statusBuffer === true)
        {
            for ($i=0, $max = count($contacts);$i<$max;++$i)
            {
                if (property_exists($resp, $i))
                    $this->statuses[(string) $resp->{$i}->id] = $resp->{$i}->status;
            }
        }
        return $resp;
    }

    public function getContactsPaginator(array $limit = null, $filter = '0')
    {
        $pKey = $filter ? (string) $filter : 'default';
        if ($this->paginator[$pKey]['limit'] == -1)
        {
            $this->paginator['limit'] = 0;
            return null;
        }
        if ($limit === null)
        {
            $limit = $this->paginator[$pKey];
        }
        else
        {
            $limit = array_values($limit);
            $limit = array(
                'offset'    => $limit[0],
                'limit'     => $limit[1]
            );
            $this->paginator[$pKey] = $limit;
        }
        $this->paginator[$pKey]['offset'] += $this->paginator[$pKey]['limit'];
        $data = $limit;
        $data['filter'] = (int) $filter;
        $data['sort'] = '';
        $action = $this->getAction(
            __METHOD__,
            array(
                'action'    => 'contact_paginator',
                'data'      => $data
            )
        );
        $result = $this->doAction($action);
        if ($result->result_code == 0)
        {
            throw new \RuntimeException(
                sprintf(
                    '%s call failed: %s (request url: %s)',
                    $action->getAction(),
                    $result->result_message,
                    (string) $action
                )
            );
        }
        return $result;
    }

    function add($params, $post_data)
    {
        $request_url = "{$this->url}&api_action=contact_add&api_output={$this->output}";
        if ($params) $request_url .= "&{$params}";
        $response = $this->curl($request_url, $post_data);
        return $response;
    }

    function delete_list($params)
    {
        $request_url = "{$this->url}&api_action=contact_delete_list&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function delete($params)
    {
        $request_url = "{$this->url}&api_action=contact_delete&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function edit($params, $post_data)
    {
        $request_url = "{$this->url}&api_action=contact_edit&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url, $post_data);
        return $response;
    }

    function list_($params)
    {
        $request_url = "{$this->url}&api_action=contact_list&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function note_add($params, $post_data)
    {
        $request_url = "{$this->url}&api_action=contact_note_add&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url, $post_data);
        return $response;
    }

    function note_edit($params, $post_data)
    {
        $request_url = "{$this->url}&api_action=contact_note_edit&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url, $post_data);
        return $response;
    }

    function note_delete($params)
    {
        $request_url = "{$this->url}&api_action=contact_note_delete&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function paginator($params)
    {
        $request_url = "{$this->url}&api_action=contact_paginator&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function sync($params, $post_data)
    {
        $request_url = "{$this->url}&api_action=contact_sync&api_output={$this->output}";
        if ($params) $request_url .= "&{$params}";
        $response = $this->curl($request_url, $post_data);
        return $response;
    }

    function tag_add($params, $post_data)
    {
        $request_url = "{$this->url}&api_action=contact_tag_add&api_output={$this->output}";
        if ($params) $request_url .= "&{$params}";
        $response = $this->curl($request_url, $post_data);
        return $response;
    }

    function tag_remove($params, $post_data)
    {
        $request_url = "{$this->url}&api_action=contact_tag_remove&api_output={$this->output}";
        if ($params) $request_url .= "&{$params}";
        $response = $this->curl($request_url, $post_data);
        return $response;
    }

    function view($params)
    {
        // can be a contact ID, email, or hash
        if (preg_match("/^email=/", $params))
        {
            $action = "contact_view_email";
        } elseif (preg_match("/^hash=/", $params))
        {
            $action = "contact_view_hash";
        } elseif (preg_match("/^id=/", $params))
        {
            $action = "contact_view";
        } else
        {
            // default
            $action = "contact_view";
        }
        $request_url = "{$this->url}&api_action={$action}&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

}
