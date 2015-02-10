<?php
namespace AC;

use AC\Models\Filter;
use AC\Models\Contact as ContactM;

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

    /**
     * @param ContactM $contact
     * @param null|int $list
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function syncContact(ContactM $contact, $list = null)
    {
        if ($list === null && $contact->getListId() === null)
        {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s expects a listID to be set on the contact OR passed as second argument',
                    __METHOD__
                )
            );
        }
        $action = $this->getAction(
            __METHOD__,
            array(
                'method'    => 'contact_sync',
                'data'      => $contact->getApiArray($list)
            )
        );
        $resp = $this->doAction(
            $action
        );
        return $resp->result_code == 1;
    }

    /**
     * @param array $contacts
     * @param null|int $list
     * @param bool $strict = false
     * @throws \RuntimeException
     */
    public function syncContacts(array $contacts, $list = null, $strict = false)
    {
        /** @var ContactM $contact */
        foreach ($contacts as $contact)
        {
            if ($contact instanceof ContactM)
            {
                if ($contact->getListId() === null)
                    $contact->setListId($list);//assume default is given if not set on model
                if (!$this->syncContact($contact) && $strict === true)
                {
                    throw new \RuntimeException(
                        sprintf(
                            'Failed to sync contact %s (%s) with list %d',
                            $contact->getContactId(),
                            $contact->getEmail(),
                            $contact->getListId()
                        )
                    );
                }
            }
        }
    }

    /**
     * @param Filter $filter
     * @return mixed
     */
    public function getContactsByFilter(Filter $filter)
    {
        $action = $this->getAction(
            __METHOD__,
            array(
                'method'    => 'contact_list',
                'data'      => $filter->toArray()
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
        $contacts = array();
        for ($i=0;isset($resp->{(string) $i});++$i)
        {
            $contacts[] = new ContactM(
                $resp->{(string) $i}
            );
        }
        return $contacts;
    }

    /**
     * @param Filter $filter
     * @param bool $merge
     * @return array|mixed
     */
    public function getAllContactsByFilter(Filter $filter, $merge = false)
    {
        $pages = array();
        do {
            $page = $this->getContactsByFilter($filter);
            if ($merge === true)
                $pages += $page;
            else
                $pages[$filter->getPage()] = $page;
            $filter->setNextPage();
        } while ($page);
        return $pages;
    }

    public function setBufferStatus($buffer = false)
    {
        if (!$buffer)
            $this->statuses = array();//clear current buffer, if exists
        $this->statusBuffer = $buffer;
        return $this;
    }

    /**
     * Get contacts by email, pass a second argument if the array consists of objects
     * The second argument will then be used as method or property to get the email value
     * Pass true as third argument to have the method return an array of
     * AC\Models\Contact instances, exceptions will be thrown if no model could be constructed
     * @param array $contact
     * @param null|string $method = null
     * @param bool $asModel = false
     * @return array<\stdClass|ContactM>
     */
    public function getContactsByEmail(array $contact, $method = null, $asModel = false)
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
            $resp = null;
            try
            {
                if ($call !== null)
                    $c = $call ? $c->{$method}() : $c->{$method};
                $resp = $this->getContactByEmail($c);
                if ($asModel === true)
                {
                    $return[$resp->email] = new ContactM(
                        $resp
                    );
                }
                else
                {
                    $return[$resp->email] = $resp;
                }
            }
            catch(\Exception $e)
            {
                if ($asModel === true)
                {
                    throw new \RuntimeException(
                        sprintf(
                            '%s not found, response: "%s" (Exception: %s)',
                            $c,
                            $resp ? json_encode($resp) : 'None',
                            get_class($e)
                        ),
                        $e->getCode(),
                        $e
                    );
                }
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

    /**
     * View everything that relates to the contacts
     * @param array $contacts
     * @return mixed
     * @throws \RuntimeException
     */
    public function getContactDetails(array $contacts)
    {
        //check argument:
        foreach ($contacts as $key => $c)
        {
            if (is_array($c))
                throw new \InvalidArgumentException(
                    sprintf(
                        '%s expects an array of ids, array given',
                        __METHOD__
                    )
                );
            $contacts[$key] = (string) $c;//cast to string
        }
        $action = $this->getAction(
            __METHOD__,
            array(
                'method'    => 'contact_list',
                'data'      => array(
                    'ids'   => implode(',', $contacts)
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
                    '%s call failed: %s.(request url: %s)',
                    $action->getAction(),
                    $resp->result_message,
                    (string) $action
                )
            );
        }
        //buffer statuses
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

    public function getContactStatus($contact, $default = 0)
    {
        $contact = (string) $contact;
        if (!isset($this->statuses[$contact]))
        {
            $this->getContacts(array($contact), true);
            if (!isset($this->statuses[$contact]))
                return (int) $default;
        }
        return (int) $this->statuses[$contact];
    }

    public function getContactStatuses(array $contacts, $default = 0)
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
        $return = $this->getContacts($contacts, true, 1);//get all contacts
        //returns either object, or array of objects
        if (is_array($return))
            unset($return['pages']);
        else
            $return = array($return);
        $pool = array();
        foreach ($return as $page)
        {
            for ($i=0;property_exists($page, (string) $i);++$i)
            {
                $pool[$page->{$i}->id] = $page->{$i}->status;
            }
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
