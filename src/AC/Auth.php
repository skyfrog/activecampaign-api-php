<?php
namespace AC;

class Auth extends ActiveCampaign
{

    function singlesignon($params)
    {
        $request_url = "{$this->url}&api_action=singlesignon&api_output={$this->output}&{$params}";
        $response = $this->curl($request_url);
        return $response;
    }

}
