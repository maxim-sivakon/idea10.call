<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('istline.call')) {
    echo "fail";
    return false;
}
$arResult = array();
$c = new \Istline\Call\Call();
$managers = $c->getManagersByClientPhone($arParams["CID"]);
$managers = array($arParams["USER_ID"]);
if (!empty($managers)) {
    $managers = $c->findOnlineManager($managers);
    if (!empty($managers)) {
        $arResult["CID"] = $arParams["CID"];
        $card = new \Istline\Call\CallCard($c);
        $arEntityType = $card->getEntityTypes();
        $entityList = $card->getEntityList();
        $communications = $card->getLinkedCommunication();
        if (!empty($entityList)) {
            foreach ($arEntityType as $type => $entClass) {
                $arResult["TYPE"][$type] = array();
                foreach ($entityList[$type] as $entity) {
                    $tmpEnt = array();
                    $tmpEnt["TITLE"] = $entity["TITLE"] ? $entity["TITLE"] : $entity["FORMATTED_NAME"];
                    $tmpEnt["ASSIGNED"] = $card->getManagerName($entity["ASSIGNED_BY_ID"]);
                    $tmpEnt["SHOW_URL"] = $entity["SHOW_URL"];
                    $res = CCrmFieldMulti::GetList(
                        array('ID' => 'asc'),
                        array('ENTITY_ID' => $type, 'ELEMENT_ID' => $entity["ID"])
                    );
                    while ($r = $res->GetNext()) {
                        if (strpos($r["COMPLEX_ID"], "PHONE") !== false) {
                            $code = "PHONE";
                        } elseif (strpos($r["COMPLEX_ID"], "MAIL") !== false) {
                            $code = "MAIL";
                        } else {
                            continue;
                        }
                        $tmpEnt[$code][] = $r["VALUE"] ? $r["VALUE"] : " ";

                    }
                    foreach ($communications as $communication => $commClass) {
                        $arResult["COMMTITLE"][$communication] = $commClass["name"];
                        if (isset($entity[$communication]) && !empty($entity[$communication])) {
                            $arComm = array();
                            foreach ($entity[$communication] as $item) {
                                $tmpComm = array();
                                $ob_comm = $commClass["class"]::GetList(false, array("ID" => $item["ID"]));
                                $comm = $ob_comm->GetNext();
                                if (isset($comm["BEGINDATE"])) {
                                    $tmpComm["DATE"] = $comm["BEGINDATE"];
                                }
                                if (isset($comm["START_TIME"])) {
                                    $tmpComm["DATE"] = $comm["START_TIME"];
                                }
                                $tmpComm["TITLE"] = $comm["TITLE"] ? $comm["TITLE"] : $comm["SUBJECT"];
                                $arComm[] = $tmpComm;
                            }
                            $tmpEnt["COMMS"][$communication] = $arComm;
                        }else{
                            $tmpEnt["COMMS"][$communication]=array();
                        }
                    }
                    $arResult["TYPE"][$type][] = $tmpEnt;
                }
            }
        }
    }
}

$this->IncludeComponentTemplate();

?>