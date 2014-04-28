<?php
namespace AC;

class Contact extends ActiveCampaign
{

    protected $statusBuffer = false;
    protected $lastPage = array();
    protected $statusses = array();
    protected $paginator = array(
        'default'   => array(
            'offset'    => 0,
            'limit'     => 50
        )
    );

    public function bufferStatusses($bufferOn = true)
    {
        $this->statusBuffer = $bufferOn;
        if (!$bufferOn)
        {
            $this->statusses = array();//remove current buffer, if exists
        }
        return $this;
    }

    public function setBufferStatus($buffer = false)
    {
        if (!$buffer)
            $this->statusses = array();
        $this->statusBuffer = $buffer;
        return $this;
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
        return $resp;
    }

    public function getContactStatus($contact, $default = 0)
    {
        $contact = (string) $contact;
        if (!isset($this->statusses[$contact]))
        {
            $this->getContacts(array($contact));
            if (!isset($this->statusses[$contact]))
                $this->statusses[$contact] = (int) $default;
        }
        return 0;
    }

    public function getContactStatusses(array $contacts, $default = 0)
    {
        $return = array();
        if ($this->statusBuffer === true)
        {
            if (empty($this->statusses))
                $this->getContacts($contacts, 1);
            //complete return array by setting default/missing ids to passed $default value
            foreach ($contacts as $contact)
                $return[(string) $contact] = $this->getContactStatus($contact, $default);
            return $return;
        }
        $pages = $this->getContacts($contacts, 1);//get all contacts
        $pool = array();
        for($i=$pages['pages'];$i<0;--$i)
        {
            foreach ($pages[$i] as $contact)
            {
                $pool[] = $contact;
            }
        }
        for ($i=0, $max = count($contacts);$i<$max;++$i)
        {
            if (isset($pool[$i]))
            {//if exists, extract status
                $return[$pool[$i]->id] = $pool[$i]->status;
            }
        }
        foreach ($contacts as $contact)
        {//check missing contacts, set to default
            $contact = (string) $contact;
            if (!isset($return[$contact]))
                $return[$contact] = $default;
        }
        return $return;
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
                    $this->statusses[(string) $resp->{$i}->id] = $resp->{$i}->status;
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
