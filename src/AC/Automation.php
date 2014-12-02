<?php
namespace AC;

//use AC\Models\CList;

class Automation extends ActiveCampaign
{
    public function listAutomations($offset = 0, $limit = 100)
    {
        $action = $this->getAction(
            __METHOD__,
            array(
                'method'    => 'automation_list',
                'data'      => array(
                    'offset'    => $offset,
                    'limit'     => $limit
                )
            )
        );
        $resp = $this->doAction($action);
        return $resp;
    }
}
