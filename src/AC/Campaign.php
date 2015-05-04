<?php
namespace AC;
use AC\Models\CampaignPaginator;
use AC\Models\Contact;
use AC\Models\Campaign as CampaignM;
use AC\Models\Interfaces\Paginator;

class Campaign extends ActiveCampaign
{

    /**
     * @param array $params
     * @return Paginator
     */
    public function paginateCampaigns(Paginator $paginator)
    {
        $action = $this->getAction(
            __METHOD__,
            array(
                'method'    => 'campaign_paginator',
                'data'      => $paginator->toArray()
            )
        );
        $response = $this->doAction(
            $action
        );
        $paginator->setResponse(
            $response
        );
        return $paginator;
    }

    /**
     * @param array $params
     * @return array
     */
    public function getAllCampaigns(array $params = null)
    {
        $paginator = new CampaignPaginator();
        if ($params)
            $paginator->setResponse($params);//allows for build setting of offset, limit, public, filter, etc...
        $campaigns = array();
        do
        {
            $campaigns = array_merge(
                $campaigns,
                $this->paginateCampaigns(
                    $paginator
                )->getData()
            );
        } while ($paginator->setNextPage()->getOffset());//if end of pagination is reached, offset is reset to 0
        return $campaigns;
    }

    /**
     * @param Models\Campaign $campaign
     * @return array
     * @throws \RuntimeException
     */
    public function getUnopenList(CampaignM $campaign)
    {
        if (!$campaign->getUnreadCount())
            return array();
        $data = array(
            'campaignid'    => $campaign->getId(),
            'messageid'     => $campaign->getMessageid()
        );
        $action = $this->getAction(
            __METHOD__,
            array(
                'method'    => 'campaign_report_unopen_list',
                'data'      => $data
            )
        );
        $list = $this->doAction(
            $action
        );
        if (!isset($list->result_code) || $list->result_code == '0')
            throw new \RuntimeException(
                sprintf(
                    'Failed to get unopen list: (HTTP: %d) %s',
                    isset($list->http_code) ? $list->http_code : 0,
                    isset($list->result_message) ? $list->result_message : 'Unknown'
                )
            );
        $contacts = array();
        for($i=0, $total = $campaign->getUnreadCount();$i<$total;++$i)
        {
            if (isset($list->{$i}))
                $contacts[] = new Contact(
                    $list->{$i}
                );
        }
        while(isset($list->{$i}))//make sure we have all contacts
            $contacts[] = new Contact(
                $list->{$i++}
            );
        return $contacts;
    }

    /**
     * @param CampaignM $campaign
     * @return $this
     * @throws \RuntimeException
     */
    public function getCampaignTotals(CampaignM $campaign)
    {
        $action = $this->getAction(
            __METHOD__,
            array(
                'method'    => 'campaign_report_unopen_list',
                'data'      => array(
                    'campaignid'    => $campaign->getId(),
                )
            )
        );
        $totals = $this->doAction(
            $action
        );
        if (!isset($totals->result_code) || $totals->result_code == '0')
            throw new \RuntimeException(
                sprintf(
                    'Failed to get unopen list: (HTTP: %d) %s',
                    isset($totals->http_code) ? $totals->http_code : 0,
                    isset($totals->result_message) ? $totals->result_message : 'Unknown'
                )
            );
        return $campaign->loadBulk($totals);
    }

    function create($params, $post_data)
    {
        $request_url = "{$this->url}&api_action=campaign_create&api_output={$this->output}";
        $response = $this->curl($request_url, $post_data);
        return $response;
    }

    function delete_list($params)
    {
        $request_url = "{$this->url}&api_action=campaign_delete_list&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function delete($params)
    {
        $request_url = "{$this->url}&api_action=campaign_delete&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function list_($params)
    {
        $request_url = "{$this->url}&api_action=campaign_list&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function paginator($params)
    {
        $request_url = "{$this->url}&api_action=campaign_paginator&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function report_bounce_list($params)
    {
        $request_url = "{$this->url}&api_action=campaign_report_bounce_list&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function report_bounce_totals($params)
    {
        $request_url = "{$this->url}&api_action=campaign_report_bounce_totals&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function report_forward_list($params)
    {
        $request_url = "{$this->url}&api_action=campaign_report_forward_list&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function report_forward_totals($params)
    {
        $request_url = "{$this->url}&api_action=campaign_report_forward_totals&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function report_link_list($params)
    {
        $request_url = "{$this->url}&api_action=campaign_report_link_list&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function report_link_totals($params)
    {
        $request_url = "{$this->url}&api_action=campaign_report_link_totals&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function report_open_list($params)
    {
        $request_url = "{$this->url}&api_action=campaign_report_open_list&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function report_open_totals($params)
    {
        $request_url = "{$this->url}&api_action=campaign_report_open_totals&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function report_totals($params)
    {
        $request_url = "{$this->url}&api_action=campaign_report_totals&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function report_unopen_list($params)
    {
        $request_url = "{$this->url}&api_action=campaign_report_unopen_list&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function report_unsubscription_list($params)
    {
        $request_url = "{$this->url}&api_action=campaign_report_unsubscription_list&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function report_unsubscription_totals($params)
    {
        $request_url = "{$this->url}&api_action=campaign_report_unsubscription_totals&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function send($params)
    {
        $request_url = "{$this->url}&api_action=campaign_send&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

    function status($params)
    {
        $request_url = "{$this->url}&api_action=campaign_status&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

}
