#!/usr/bin/php -q
<?php
/*
Данный скрипт отправляет данные на портал и принимает от него ответ, который затем передается в диалплан АТС. Файл должен находится в каталоге /var/lib/asterisk/agi-bin
*/
include("phpagi.php");
$agi = new AGI;
$result = array();

$portalAdr="portal.dev";
$protocol="http";

foreach ($argv as $key => $arg) {
    if ($key) {
        $r = explode("=", $arg);
        $result[$r[0]] = $r[1];
    }
}

if ($result['action'] == 'start_monitor') {
    $numbstr = file_get_contents($protocol.'://'.$portalAdr.'/bitrix/tools/istlineCall/istlineCall.php?phonenumber=' . $result["tel"] . "&action=" . $result['action'] . "&uid=" . $result['uid'] . "&file=" . $result['file'] . "&did=" . $result['DID']);
    $arr = unserialize($numbstr);
    foreach ($arr as $key => $group) {
        if ($group['method'] == 'random') {
            shuffle($group["numbs"]);
        }
        $strnum = false;
        foreach ($group["numbs"] as $num) {
            if (!$strnum) {
                $strnum = trim($num);
            } else {
                $strnum .= ", " . trim($num);
            }
        }
        $agi->set_variable("method", $group['method']);
        $agi->set_variable("result" . $key, $strnum);
        $agi->set_variable("delay", $group['delay']);
        $agi->set_variable("mail", $group['mail']);
    }
    $agi->set_variable("count", count($arr));
    exit(0);
} elseif ($result['action'] == 'out_call') {
    $url = $protocol.'://'.$portalAdr.'/bitrix/tools/istlineCall/istlineCall.php';
    $post_data = array(
        "phonenumber" => $result["tel"],
        "action" => $result['action'],
        "uid" => $result['uid'],
        "innernumb" => $result['innernumb']
    );
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $output = curl_exec($ch);
    curl_close($ch);
    exit(0);
}


