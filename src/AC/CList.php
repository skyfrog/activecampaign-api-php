<?php
namespace AC;
use AC\Arguments\Action,
    AC\Arguments\Config;

class CList extends ActiveCampaign
{

    private static $lists = null;

    public function getListByName($name)
    {
        $lists = $this->getLists();
        foreach ($lists as $list)
        {
            if ($list->name === $name)
                return $list;
        }
        return null;
    }

    public function getListById(array $ids)
    {
        $lists = $this->getLists();
        $return = array();
        foreach ($lists as $list)
        {
            if (in_array($list->id, $ids))
                $return[$list->id] = $list;
        }
        return $return;
    }

    protected function getLists()
    {
        if (self::$lists === null)
        {
            $action = new Action(
                array(
                    'method' => 'list_list',
                    'output' => $this->output,
                    'data'   => array('ids' => 'all')
                )
            );
            $lists = $this->doAction($action, array('ids'=>'all'));
            if ((int) $lists->result_code !== 1)
            {
                throw new \RuntimeException(
                    sprintf(
                        'Failed to get list overview: %s - %d (@%s)',
                        $lists->result_message,
                        (int)$lists->result_code,
                        __METHOD__
                    )
                );
            }
            self::$lists = array();
            foreach ($lists as $name => $val)
            {
                if ($val instanceof \stdClass)
                {
                    self::$lists[] = $val;
                }
            }
        }
        return self::$lists;
    }

    function add($params, $post_data)
    {
        $request_url = "{$this->url}&api_action=list_add&api_output={$this->output}";
        $response = $this->curl($request_url, $post_data);
        return $response;
    }

    function delete_list($params)
    {
        $request_url = "{$this->url}&api_action=list_delete_list&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function delete($params)
    {
        $request_url = "{$this->url}&api_action=list_delete&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function edit($params, $post_data)
    {
        $request_url = "{$this->url}&api_action=list_edit&api_output={$this->output}";
        $response = $this->curl($request_url, $post_data);
        return $response;
    }

    function field_add($params, $post_data)
    {
        $request_url = "{$this->url}&api_action=list_field_add&api_output={$this->output}";
        $response = $this->curl($request_url, $post_data);
        return $response;
    }

    function field_delete($params)
    {
        $request_url = "{$this->url}&api_action=list_field_delete&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function field_edit($params, $post_data)
    {
        $request_url = "{$this->url}&api_action=list_field_edit&api_output={$this->output}";
        $response = $this->curl($request_url, $post_data);
        return $response;
    }

    function field_view($params)
    {
        $request_url = "{$this->url}&api_action=list_field_view&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function list_($params, $post_data)
    {
        if ($post_data)
        {
            if (isset($post_data["ids"]) && is_array($post_data["ids"]))
            {
                // make them comma-separated.
                $post_data["ids"] = implode(",", $post_data["ids"]);
            }
        }
        $request_url = "{$this->url}&api_action=list_list&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url, $post_data);
        return $response;
    }

    function paginator($params)
    {
        $request_url = "{$this->url}&api_action=list_paginator&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function view($params)
    {
        $request_url = "{$this->url}&api_action=list_view&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

}
