<?php
namespace AC;

class Group extends ActiveCampaign
{

    function add($params, $post_data)
    {
        $request_url = "{$this->url}&api_action=group_add&api_output={$this->output}";
        $response = $this->curl($request_url, $post_data);
        return $response;
    }

    function delete_list($params)
    {
        $request_url = "{$this->url}&api_action=group_delete_list&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function delete($params)
    {
        $request_url = "{$this->url}&api_action=group_delete&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function edit($params, $post_data)
    {
        $request_url = "{$this->url}&api_action=group_edit&api_output={$this->output}";
        $response = $this->curl($request_url, $post_data);
        return $response;
    }

    function list_($params)
    {
        $request_url = "{$this->url}&api_action=group_list&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function view($params)
    {
        $request_url = "{$this->url}&api_action=group_view&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

}
