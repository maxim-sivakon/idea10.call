<?php

namespace Istline\Call;

IncludeModuleLangFile(__FILE__);

class CallCard
{
    private $cid;
    private $entityList;
    private $call;

    /**
     * @param $call Call
     */
    public function __construct($call)
    {
        $this->call = $call;
        $this->cid = $this->call->getCid();
        $this->entityList = $this->_getEntityList($this->cid);
    }

    public function showUser($userID = 0,$params)
    {
        if(\CModule::IncludeModule('pull')){
            if(is_array($userID)) {
                foreach ($userID as $user) {
                    \CPullStack::AddByUser($user, Array(
                        'module_id' => 'istline.call',
                        'command' => 'start_call',
                        'params' => Array(
                            "phone" => $params["PHONE"],
                            "actID" => $params["ACT"],
                            "usID" => $user
                        ),
                    ));
                }
            }else{
                \CPullStack::AddByUser($userID, Array(
                    'module_id' => 'istline.call',
                    'command' => 'start_call',
                    'params' => Array(
                        "phone" => $params["PHONE"],
                        "actID" => $params["ACT"],
                        "usID" => $userID
                    ),
                ));

            }
        }
    }
    public function closeCard($userID = 0,$params)
    {
        if(\CModule::IncludeModule('pull')){
            if(is_array($userID)) {
                foreach ($userID as $user) {
                    \CPullStack::AddByUser($user, Array(
                        'module_id' => 'istline.call',
                        'command' => 'close_call',
                        'params' => Array(
                            "phone" => $params["PHONE"],
                            "actID" => $params["ACT"],
                            "usID" => $user
                        ),
                    ));
                }
            }else{
                \CPullStack::AddByUser($userID, Array(
                    'module_id' => 'istline.call',
                    'command' => 'close_call',
                    'params' => Array(
                        "phone" => $params["PHONE"],
                        "actID" => $params["ACT"],
                        "usID" => $userID
                    ),
                ));
            }
        }
    }

    public function getEntityList()
    {
        return $this->entityList;
    }

    private function _getEntityList()
    {
        $entityList = $this->call->getEntityListByPhone($this->cid);
        return $entityList;
    }

    public function getEntityTypes()
    {
        return array(
            "COMPANY" => 'CCrmCompany',
            "CONTACT" => 'CCrmContact',
            "LEAD" => 'CCrmLead'
        );
    }

    public function getLinkedCommunication()
    {
        return array(
            "DEALS" => array(
                'class' => 'CCrmDeal',
                'name' => GetMessage("ISTLINE_CALL_SDELKI")
            ),
            "ACTIVITIES" => array(
                'class' => 'CCrmActivity',
                'name' => GetMessage("ISTLINE_CALL_DELA")
            ),
        );
    }

    public function getManagerName($id)
    {
        $ob_user = \CUser::GetByID($id);
        $user = $ob_user->Fetch();
        return \CUser::FormatName("#LAST_NAME# #NAME#", $user);
    }
}