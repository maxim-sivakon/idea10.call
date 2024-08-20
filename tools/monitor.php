<?php
define("NOT_CHECK_PERMISSIONS", true);
$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__) . "/../../../..");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
IncludeModuleLangFile(__FILE__);
use Bitrix\Main\Type as FieldType;

if (!CModule::IncludeModule('crm')) {
    return false;
}
if (!CModule::IncludeModule('istline.call')) {
    return false;
}

global $callID, $files, $params, $c;

$c=new Istline\Call\Call();

function myDumpCall($ecode, $data, $server, $port){
    global $c, $params;
    $c->dump_events($ecode, $data, $server, $port,$params);
}

$fileslist = $c->getMonitorFiles();

foreach ($fileslist as $file) {
    if ($file != "." && $file != ".." && file_exists($c->tmpDIR . 'calls/' . $file)) {
$c->debug("callID ".$file);
        $callID=$file;
        $params=$c->prepareCallMonitoring($file);
        $c->manager->connect();
        $c->waitFile=false;
        $c->manager->add_event_handler("*", 'myDumpCall');
        $t = $c->manager->wait_response("Hangup", "Uniqueid", $file);
        $c->manager->disconnect();
        usleep(2000000);
        $f = $c->getFile($params["FILE"], $callID);
$c->debug("File:");
$c->debug($f);
        $c->saveFileToActiv($params["ACT"],$f);
    }
}
exit(0);
