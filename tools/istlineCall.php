<?
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
IncludeModuleLangFile(__FILE__);

set_time_limit(15);

if (!CModule::IncludeModule('crm')) {
    return false;
}
if (!CModule::IncludeModule('istline.call')) {
    return false;
}
if (!CModule::IncludeModule('pull')) {
    return false;
}
global $c, $params, $callID;

$c = new \Istline\Call\Call(true);

function myDumpCall($ecode, $data, $server, $port)
{
    global $c, $params;
    $c->dump_events($ecode, $data, $server, $port, $params);
}


if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "start_monitor") {
    $phones = [];
    if (isset($_REQUEST["did"])) {
        $trunc = $_REQUEST["did"] ? $_REQUEST["did"] : false;
    }
    if (strlen($_REQUEST["phonenumber"]) == 10) {
        $_REQUEST["phonenumber"] = '8' . $_REQUEST["phonenumber"];
    }
    $entList = $c->getEntityListByPhone($_REQUEST["phonenumber"]);
    $managers = $c->getManagerList($entList);
    $phones[] = $c->getInnerPhonesManagers($managers);
    if (empty($entList)) {
        $entList = $c->addLead(reset($c->par["crm_manager"]), $_REQUEST["phonenumber"]);
    }
    $activityId = $c->getEntAndAddCall($entList, $_REQUEST["phonenumber"], true);
    $phones = array_merge($phones, $c->getPhonesFromTruncSettings($trunc));

    $data = "ACT=" . $activityId . ",FILE=" . $_REQUEST["file"] . ",PHONE=" . $_REQUEST["phonenumber"];

    $c->saveInfo($_REQUEST["uid"], $data);

    $c->runInBackground(dirname(__FILE__) . DIRECTORY_SEPARATOR . "monitor.php");

    echo $c->prepareNumbForPBX($phones, $trunc);
    exit(0);
}

if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "start_call") {
    if (isset($_REQUEST["phonenumber"]) && is_numeric($_REQUEST["phonenumber"])) {
        $APPLICATION->IncludeComponent(
            "istline:callCard",
            "",
            [
                "COMPONENT_TEMPLATE" => ".default",
                "CID" => $_REQUEST["phonenumber"],
                "USER_ID" => $USER->GetID(),
                "ACT_ID" => $_REQUEST["actID"],
            ],
            false
        );
    }
    exit(0);
}

if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "end_call") {
    if (isset($_REQUEST["act_id"])) {
        $arFields = [];
        $arFields['DESCRIPTION'] = $_REQUEST["text"];
        $m = CCrmActivity::Update(str_replace("act", "", $_REQUEST["act_id"]), $arFields, false);
    }
    exit(0);
}

if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "out_call" && $c->par["crm_call_fix_all"] == "Y") {

    global $callID, $files, $activityId;
    $entList = $c->getEntityListByPhone($_REQUEST["phonenumber"]);
    $managID = $c->getManagersByInnerPhone($_REQUEST["innernumb"]);
    if (empty($entList)) {
        $owner = empty($managID) ? reset($c->par["crm_manager"]) : reset($managID);
        $entList = $c->addLead($owner, $_REQUEST["phonenumber"]);
    }
    $managers = $c->getManagerList($entList);
    $phones = [$managID => $_REQUEST["innernumb"]];
    $params["ACT"] = $c->getEntAndAddCall($entList, $_REQUEST["phonenumber"], false, $managID[0] ? $managID[0] : 1);
    $callID = $_REQUEST["uid"];

    $c->waitFile = true;
    $c->manager->connect();
    $c->manager->add_event_handler("*", 'myDumpCall');
    $c->manager->wait_response("Hangup", "Uniqueid", $callID);
    exit(0);
}

if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'call-from-portal') {
    $obUser = $USER->GetByID($USER->GetID());
    $arUser = $obUser->GetNext();
    if (!$arUser["UF_PHONE_INNER"]) {
        echo GetMessage("ISTLINE_CALL_INNER_NUMB_FALSE");
        die();
    }
    $c->debug("Start call from " . $arUser["UF_PHONE_INNER"] . " to " . $_REQUEST["targetN"]);

    $callID = false;
    $files = [];
    $activityId = $_REQUEST["eventID"];
    $c->debug("owner " . $_REQUEST["ownerID"]);

    $c->debug("ownerType " . ucfirst(strtolower($_REQUEST["ownerType"])));

    if (!$activityId && isset($_REQUEST["ownerID"]) && $_REQUEST["ownerID"] && isset($_REQUEST["ownerType"]) && $_REQUEST["ownerType"] && isset($_REQUEST["targetN"]) && $_REQUEST["targetN"]) {
        $activityId = $c->AddCall([
            "ownerID" => $_REQUEST["ownerID"],
            "ownerType" => ucfirst(strtolower($_REQUEST["ownerType"])),
            "USER_ID" => $USER->GetID(),
            "USER_NAME" => $USER->GetFullName(),
            "PHONE_NUMBER" => $_REQUEST["targetN"],
            "START_TIME" => date($DB->DateFormatToPHP(CLang::GetDateFormat("FULL"))),
        ]);
    }
    if ($activityId) {
        $c->debug("activity " . $activityId);

        if (isset($_REQUEST["targetN"]) && $_REQUEST["targetN"]) {
            $params["ACT"] = $activityId;
            $c->manager->connect();
            $c->manager->add_event_handler("*", 'myDumpCall');
            $randID = randString();

            if(($arUser["UF_PHONE_INNER"] >= 40 && $arUser["UF_PHONE_INNER"] <= 69) || ($arUser["UF_PHONE_INNER"] >= 300 && $arUser["UF_PHONE_INNER"] <= 599)){
                $tech = 'PJSIP';
            } else{
                $tech = 'SIP';
            }

            //$tech = ($arUser["UF_PHONE_INNER"] >= 40 && $arUser["UF_PHONE_INNER"] <= 69) ? 'PJSIP' : 'SIP';
            $t = $c->manager->Originate(
                $tech . '/' . $arUser["UF_PHONE_INNER"],
                $c->par["pbx_prefix"] . $_REQUEST["targetN"],
                "UNREST",
                '1',
                NULL,
                NULL,
                "30000",
                $arUser["UF_PHONE_INNER"],
                "source=from_portal",
                NULL,
                true,
                $randID
            );
            $t = $c->manager->wait_response(["OriginateResponse", "Newchannel", "NewCallerid"], "CallerIDNum", $arUser["UF_PHONE_INNER"], 30);
            if (isset($t["Response"]) && $t["Response"] == "Failure") {
                $t = $c->manager->Originate(
                    $tech . '/' . $arUser["UF_PHONE_INNER"],
                    $c->par["pbx_prefix"] . $_REQUEST["targetN"],
                    "from-internal",
                    '1',
                    NULL,
                    NULL,
                    "30000",
                    $arUser["UF_PHONE_INNER"],
                    "source=from_portal",
                    NULL,
                    true,
                    $randID
                );
                $t = $c->manager->wait_response(["OriginateResponse", "Newchannel", "NewCallerid"], "CallerIDNum", $arUser["UF_PHONE_INNER"], 30);
                $callID = $t["Uniqueid"];
                if (isset($t["Response"]) && $t["Response"] == "Failure") {
                    die("fail!!!");
                }
            } else {
                $callID = $t["Uniqueid"];
            }
            if (!$callID) {
                echo GetMessage("ISTLINE_CALL_FALSE");
                die();
            }
            $c->debug("callID " . $callID);

            $c->waitFile = true;
            $data = "ACT=" . $activityId . ",PHONE=" . $_REQUEST["targetN"] . ",OUTBOUND=1,FILE=";

            $c->saveInfo($callID, $data);
            $c->runInBackground(dirname(__FILE__) . DIRECTORY_SEPARATOR . "monitor.php");
            $c->manager->disconnect();
            exit(0);
        }
    } elseif (isset($_REQUEST["targetN"]) && $_REQUEST["targetN"] && strlen($_REQUEST["targetN"]) == $c->par["pbx_inner_len"]) {
        $c->manager->connect();
        $randID = randString();
        $t = $c->manager->Originate(
            $tech . '/' . $arUser["UF_PHONE_INNER"],
            $_REQUEST["targetN"],
            "from-internal",
            '1',
            NULL,
            NULL,
            "30000",
            $arUser["UF_PHONE_INNER"],
            "source=from_portal",
            NULL,
            true,
            $randID
        );
        $t = $c->manager->wait_response(["OriginateResponse", "Newchannel", "NewCallerid"], "CallerIDNum", $arUser["UF_PHONE_INNER"]);
        $callID = $t["Uniqueid"];

        $c->manager->disconnect();
    }

}


require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
