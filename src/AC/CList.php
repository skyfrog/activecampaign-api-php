<?php
namespace AC;
use AC\Arguments\Action,
    AC\Models\CList as ListModel;

class CList extends ActiveCampaign
{

    private static $lists = null;

    /**
     * @param $name
     * @return null|\AC\Models\CList
     */
    public function getListByName($name)
    {
        $lists = $this->getLists();
        foreach ($lists as $list)
        {
            if ($list->getName() === $name)
                return $list;
        }
        return null;
    }

    /**
     * @param array $ids
     * @return array|\AC\Models\CList
     */
    public function getListById(array $ids)
    {
        $lists = $this->getLists();
        $total = count($ids);
        $current = 0;
        $return = array();
        foreach ($lists as $list)
        {
            if (in_array($list->getId(), $ids))
            {
                ++$current;
                $return[$list->getId()] = $list;
            }
            if ($current === $total)
                break;
        }
        if ($total === 1 && $current)
            return $return[0];
        return $return;
    }

    /**
     * @return array of \AC\Models\CList|null
     * @throws \RuntimeException
     */
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
            foreach ($lists as $val)
            {
                if ($val instanceof \stdClass)
                {
                    $val = (array) $val;
                }
                if (is_array($val))
                    self::$lists[] = new ListModel($val);
            }
        }
        return self::$lists;
    }

    public function addList(ListModel $list)
    {
        $action = $this->getAction(
            __METHOD__,
            array(
                'method'    => 'list_add'
            )
        );
        $action->setFullData(false);
        $action->setData(
            $list
        );
        $result = $this->doAction($action);
        if (!$this->config->getRetryFullData() || $result->result_code == 1)
            return $result;
        return $this->doAction(
                $action->setFullData()
        );
    }

    public function deleteList(ListModel $list)
    {
        $action = $this->getAction(
            __METHOD__,
            array(
                'method'    => 'list_delete'
            )
        );
        return $this->doAction(
            $action->setData(
                array(
                    'id'    => $list->getId()
                )
            )
        );
    }

    public function deleteLists(array $lists, $detailed = true)
    {
        if ($detailed === false)
        {
            $action = $this->getAction(
                __METHOD__,
                array(
                    'method'    => 'list_delete_list'
                )
            );
            $data = array();
            foreach ($lists as $list)
            {
                if (!$list instanceof ListModel)
                {
                    $list = new ListModel(
                        is_array($list) ? $list : array('id' => $list)
                    );
                }
                $data[] = $list->getId();
            }
            $action->setData(
                array(
                    'ids'   => implode(',',$data)
                )
            );
            return $this->doAction(
                $action
            );
        }
        $responses = array();
        foreach ($lists as $list)
        {
            if (!$list instanceof ListModel)
            {
                $list = new ListModel(
                    is_array($list) ? $list : array('id' => $list)
                );
            }
            $responses[$list->getId()] = $this->deleteList(
                $list
            );
        }
        return $responses;
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

    public function __destruct()
    {
        foreach ($this->actions as $action)
        {//remove data, avoid dangling pointers (ref-count GC)
            $action->setData('');
        }
        $this->actions = null;
        //ignore other instances, lazy-loader method will perform API call again if ever this is required...
        self::$lists = null;
    }

}
