<?php
namespace Istline\Call;
use Bitrix\Main\UserTable;

\IncludeModuleLangFile(__FILE__);

class Call
{
    public $err;
    private $managerList = array();
    private $entityList = array();
    private $cid;
    private $activeManager = array();
    public $par, $tmpDIR, $waitFile;

    public function mylog($data)
    {
        $data = print_r($data, true);
        $data = date('[Y-m-d H:i:s] - ') . $data . PHP_EOL;
        file_put_contents($_SERVER["DOCUMENT_ROOT"] . '/bitrix/tmp/mylog.log', $data, FILE_APPEND);
    }

    public function __construct($test = false)
    {
        global $DB;
        global $USER;

        $dbUser = \Bitrix\Main\UserTable::getList([
            'select' => [
                "ID",
                "PERSONAL_COUNTRY"
            ],
            'filter' => [
                'ID' => $USER->GetID()
            ]
        ])->fetch();

        /*
         * (RU) - Россия - code: 1
         * (BY) - Беларусь - code: 4
         * (KZ) - Казахстан - code: 6
         * (UZ) - Узбекистан - code: 13
         * (KG) - Киргизия - code: 7
         * (TR) - Турция - code: 127
         * */

        switch ($dbUser[ "PERSONAL_COUNTRY" ]) {
            case 1:
                $countryCode = 'RU';
                break;
            case 4:
                $countryCode = 'BY';
                break;
            case 13:
                $countryCode = 'UZ';
                break;
            case 7:
                $countryCode = 'KG';
                break;
            case 127:
                $countryCode = 'TR';
                break;
            case 6:
                $countryCode = 'KZ';
                break;
            default:
                $countryCode = 'RU';
                break;
        }

        set_time_limit($test ? 100 : 0);
        $DB->Query("SET wait_timeout=" . ($test ? 100 : 28800));
        $this->err = array();
        $this->par = array(
            "pbx_type" => \COption::GetOptionString("istline.call", "pbx_type_" . $countryCode),
            "pbx_prefix" => \COption::GetOptionString("istline.call", "pbx_prefix_" . $countryCode),
            "pbx_protocol" => \COption::GetOptionString("istline.call", "pbx_protocol_" . $countryCode),
            "pbx_ip" => \COption::GetOptionString("istline.call", "pbx_ip_" . $countryCode),
            "pbx_auth_url" => \COption::GetOptionString("istline.call", "pbx_auth_url_" . $countryCode),
            "pbx_admin_user" => \COption::GetOptionString("istline.call", "pbx_admin_user_" . $countryCode),
            "pbx_admin_pass" => \COption::GetOptionString("istline.call", "pbx_admin_pass_" . $countryCode),
            "pbx_ami_user" => \COption::GetOptionString("istline.call", "pbx_ami_user_" . $countryCode),
            "pbx_ami_pass" => \COption::GetOptionString("istline.call", "pbx_ami_pass_" . $countryCode),
            "pbx_ami_port" => \COption::GetOptionString("istline.call", "pbx_ami_port_" . $countryCode),
            "cdr_path" => \COption::GetOptionString("istline.call", "cdr_path_" . $countryCode),
            "cdr_salt" => \COption::GetOptionString("istline.call", "cdr_salt_" . $countryCode),
            "pbx_inner_len" => \COption::GetOptionString("istline.call", "pbx_inner_len_" . $countryCode),
            "crm_manager" => unserialize(\COption::GetOptionString("istline.call", "crm_manager_" . $countryCode)),
            "crm_call_variant" => \COption::GetOptionString("istline.call", "crm_call_variant_" . $countryCode),
            "crm_call_delay" => \COption::GetOptionString("istline.call", "crm_call_delay_" . $countryCode),
            "crm_call_lame" => \COption::GetOptionString("istline.call", "crm_call_lame_" . $countryCode),
            "crm_call_fix_all" => \COption::GetOptionString("istline.call", "crm_call_fix_all_" . $countryCode),
        );
        $this->waitFile = false;
        if (!$_SERVER["DOCUMENT_ROOT"]) {
            $_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__) . "/../../../..");
        }
        $this->tmpDIR = $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . "bitrix" . DIRECTORY_SEPARATOR . "tmp" . DIRECTORY_SEPARATOR;
        $this->manager = new \Istline\Call\CMyAGI_AsteriskManager(false, array(
            "server" => $this->par["pbx_ip"],
            "port" => $this->par["pbx_ami_port"],
            "username" => $this->par["pbx_ami_user"],
            "secret" => $this->par["pbx_ami_pass"]
        ), $test);
        $dids = $this->getTruncs();
        foreach ($dids as $did) {
            $this->par["crm_did_use_default_manager_" . $did] = \COption::GetOptionString("istline.call", "crm_did_use_default_manager_" . $did . "_" . $countryCode);
            $this->par["crm_did_manager_" . $did] = unserialize(\COption::GetOptionString("istline.call", "crm_did_manager_" . $did . "_" . $countryCode));
            $this->par["mail_" . $did] = \COption::GetOptionString("istline.call", "mail_" . $did . "_" . $countryCode);
        }
        $this->par["_pref"] = array(
            "usedef" => "crm_did_use_default_manager_",
            "didmanag" => "crm_did_manager_",
        );

    }

    public function getEntityListByPhone($phone)
    {
        $result = array();
        if (is_numeric($phone)) {
            $arPhone = $this->preparePhone($phone);
            $this->cid = $phone;
        } else {
            $this->err[] = GetMessage("ISTLINE_CALL_UKAZAN_NE_KORREKTNYY");
            return false;
        }
        foreach ($arPhone as $phone) {
            $crm = \CCrmSipHelper::findByPhoneNumber($phone, array('USER_ID' => $userId));
            if (!empty($crm)) {
                foreach ($crm as $type => $items) {
                    if ($result[$type]) {
                        foreach ($result[$type] as $index => $elem) {
                            foreach ($items as $i => $e) {
                                if ($elem["ID"] == $e["ID"]) {
                                    unset($items[$i]);
                                }
                            }
                        }
                        $result[$type] = array_merge($result[$type], $items);
                    } else {
                        $result[$type] = $items;
                    }
                }
            }
        }
        return $result;
    }

    private function preparePhone($phone)
    {
        $result = array();
        $len = strlen($phone);
        if ($len < 5) {
            $this->err[] = GetMessage("ISTLINE_CALL_SLISKOM_KOROTKIY_NOM");
            return false;
        }
        $this->phone = $phone;
        for ($i = 5; $i <= $len; $i++) {
            $result[] = substr($phone, -$i);
        }
        if ($len == 10) {
            $result[] = "8" . $phone;
            $result[] = "7" . $phone;
            $this->phone = "8" . $phone;
        }
        return $result;
    }


    public function getManagerList($arEntity = array())
    {
        $checSum = md5(serialize($arEntity));
        if (isset($this->managerList[$checSum])) {
            $result = $this->managerList[$checSum];
        } else {
            $result = $this->_getManagerList($arEntity);
            $this->managerList[$checSum] = $result;
        }
        return $result;
    }

    private function _getManagerList($arEntity = array())
    {
        $result = array();
        if (!empty($arEntity)) {
            foreach ($arEntity as $type => $items) {
                foreach ($items as $item) {
                    $result[] = $item["ASSIGNED_BY_ID"];
                }
            }
        }
        $result = array_unique($result);
        return $result;
    }

    public function getInnerPhonesManagers($arManagers = array())
    {
        $result = array();
        if (!empty($arManagers)) {
            global $USER;
            foreach ($arManagers as $manager) {
                $obUser = $USER->GetByID($manager);
                $user = $obUser->Fetch();
                if ($user["UF_PHONE_INNER"]) {
                    $result[$manager] = preg_replace("|\D.+|", "", $user["UF_PHONE_INNER"]);
                }
            }
            $result = array_unique($result);
        }
        return $result;
    }

    public function runInBackground($cmd)
    {
        if (PHP_OS == "WINNT") {
            $a = popen('start /B ' . "php " . $cmd, 'r');
            $b = pclose($a);
        } else {
            $a = popen("php " . $cmd . " 2>1&", "r");
            $b = pclose($a);
        }
        usleep(500000);
        return (isset($pid[0])) ? $pid[0] : false;
    }

    public function AddCall($params)
    {
        $crmEntityType = $params['ownerType'];
        $crmEntity = array(
            "ENTITY_TYPE" => $crmEntityType === intval($crmEntityType) ? $crmEntityType : constant('\CCrmOwnerType::' . $crmEntityType),
            "ENTITY_ID" => $params["ownerID"]
        );

        if (!$crmEntity) {
            return false;
        }

        $direction = isset($params['INCOMING']) && intval($params['INCOMING']) === 1
            ? \CCrmActivityDirection::Incoming
            : \CCrmActivityDirection::Outgoing;

        $arFields = array(
            'TYPE_ID' => \CCrmActivityType::Call,
            'SUBJECT' => isset($params['INCOMING']) && intval($params['INCOMING']) === 1 ? GetMessage("ISTLINE_CALL_VHODASIY_ZVONOK") : GetMessage("ISTLINE_CALL_ISHODASIY_ZVONOK"),
            'START_TIME' => $params["START_TIME"],
            'COMPLETED' => 'N',
            'PRIORITY' => \CCrmActivityPriority::Medium,
            'DESCRIPTION' => '',
            'DESCRIPTION_TYPE' => \CCrmContentType::PlainText,
            'LOCATION' => '',
            'DIRECTION' => $direction,
            'NOTIFY_TYPE' => \CCrmActivityNotifyType::None,
            'BINDINGS' => array(),
            'SETTINGS' => array(),
            'AUTHOR_ID' => $params['USER_ID']
        );
        $arFields['RESPONSIBLE_ID'] = $params['USER_ID'];
        $arFields['BINDINGS'][] = array(
            'OWNER_ID' => $crmEntity['ENTITY_ID'],
            'OWNER_TYPE_ID' => $crmEntity['ENTITY_TYPE']
        );

        $arComms = array(
            array(
                'ID' => 0,
                'TYPE' => 'PHONE',
                'VALUE' => $params['PHONE_NUMBER'],
                'ENTITY_ID' => $crmEntity['ENTITY_ID'],
                'ENTITY_TYPE_ID' => $crmEntity['ENTITY_TYPE']
            )
        );
        $ID = \CCrmActivity::Add($arFields, false, true, array('REGISTER_SONET_EVENT' => true));
        if ($ID > 0) {
            \CCrmActivity::SaveCommunications($ID, $arComms, $arFields, true, false);
        }

        return $ID;
    }

    public function getMonitorFiles()
    {
        $files = array();
        $dh = opendir($this->tmpDIR . "calls");
        while (false !== ($filename = readdir($dh)) && $dh) {
            $files[] = $filename;
        }
        return $files;
    }

    public function prepareCallMonitoring($file)
    {
        $result = array();
        $content = file_get_contents($this->tmpDIR . 'calls' . DIRECTORY_SEPARATOR . $file);
        $pars = explode(",", $content);
        foreach ($pars as $param) {
            $parsedpar = explode("=", $param);
            $result[$parsedpar[0]] = $parsedpar[1];
        }
        unlink($this->tmpDIR . 'calls' . DIRECTORY_SEPARATOR . $file);
        return $result;
    }

    public function getManagersByClientPhone($clientPhone)
    {
        $result = array();
        $entList = $this->getEntityListByPhone($clientPhone);
        if (empty($entList)) {
            $result = unserialize($this->par["crm_manager"]);
        } else {
            $result = $this->getManagerList($entList);
        }
        return $result;
    }

    public function findOnlineManager($managers)
    {
        $result = array();
        foreach ($managers as $manager) {
            if (\CUser::IsOnLine($manager)) {
                $result[] = $manager;
            }
        }
        return $result;
    }

    public function getCid()
    {
        return $this->cid;
    }

    public function prepareNumbForPBX($phones, $did)
    {
        $res = array();
        if (is_array($phones)) {
            foreach ($phones as $group) {
                $res[] = array(
                    'method' => $this->par["crm_call_variant"],
                    'delay' => $this->par["crm_call_delay"],
                    'numbs' => $group,
                    'mail' => $did ? $this->par["mail_" . $did] : ""
                );
            }
        }
        return serialize($res);
    }

    public function getManagersByInnerPhone($numbs)
    {
        $result = array();

        if (!empty($numbs) || $numbs) {
            global $USER;
            $obUsers = $USER->GetList($sort, $order, array('UF_PHONE_INNER' => $numbs));
            while ($us = $obUsers->GetNext()) {
                $result[] = $us["ID"];
            }
            $result = array_unique($result);
        }
        return $result;
    }

    public function addActiveManager($managerPhone = false, $cid = false)
    {
        if ($managerPhone && $cid) {
            $this->activeManager[$managerPhone] = $cid;
        }
    }

    public function removeActiveManagers($managerPhone = false)
    {
        if ($managerPhone) {
            unset($this->activeManager[$managerPhone]);
        }
    }

    public function getActiveManagers()
    {
        return $this->activeManager;
    }

    public function getTruncs()
    {
        if($this->manager->connect()) {
            $t = $this->manager->Command("dialplan show ext-did-0002");
            $list = preg_match_all("/'(\d+)'/m", $t["data"], $mat);
            $this->manager->disconnect();
            return $mat[1];
        }else{
            $this->err[]="Check settings";
            return array();
        }
    }

    public function getPhonesFromTruncSettings($trunc = false)
    {
        $result = array();
        if ($trunc) {
            $result[] = $this->getInnerPhonesManagers($this->par[$this->par["_pref"]["didmanag"] . $trunc]);
            if ($this->par[$this->par["_pref"]["usedef"] . $trunc] == "Y") {
                $result[] = $this->getInnerPhonesManagers($this->par["crm_manager"]);
            }
        }
        return $result;
    }

    private function _normalize_key(&$data)
    {
        $newdata = array();
        foreach ($data as $key => $val) {
            $newdata[strtolower($key)] = $val;
        }
        $data = $newdata;
    }

    private function _getRndIV($iv_len)
    {
        $iv = '';
        while ($iv_len-- > 0) {
            $iv .= chr(mt_rand() & 0xff);
        }
        return $iv;
    }

    private function _encrypt($str, $salt, $iv_len = 16)
    {
        $salt = $this->par["cdr_salt"];
        $str .= "\x13";
        $n = strlen($str);
        if ($n % 16) $str .= str_repeat("\0", 16 - ($n % 16));
        $i = 0;
        $enc_text = $this->_getRndIV($iv_len);
        $iv = substr($salt ^ $enc_text, 0, 512);
        while ($i < $n) {
            $block = substr($str, $i, 16) ^ pack('H*', md5($iv));
            $enc_text .= $block;
            $iv = substr($block . $iv, 0, 512) ^ $salt;
            $i += 16;
        }
        return base64_encode($enc_text);
    }

    private function _get_url($tmplurl, $repl = array())
    {
        foreach ($repl as $key => $val) {
            $tmplurl = preg_replace("|" . $key . "|", $val, $tmplurl);
        }
        return $tmplurl;
    }

    public function debug($data, $suff = '', $pref = '')
    {
$this->par["crm_call_debug"]="Y";
        if ($this->par["crm_call_debug"] == 'Y') {
            $data = print_r($data, true);
            $data = date('[Y-m-d H:i:s] - ') . $data . PHP_EOL;
            global $callID;
            if ($callID) {
                $suff .= $callID;
            }
            global $params;
            if ($params["PHONE"]) {
//                $pref .= $params["PHONE"];
            }
            file_put_contents($_SERVER["DOCUMENT_ROOT"] . '/bitrix/tmp/' . $pref . '_call_debug_' . $suff . '.log', $data, FILE_APPEND);
        }
    }


    public function getFile($file=false, $key)
    {
        if (strpos($file, $this->par["cdr_path"]) !== 0) {
            $file = $this->par["cdr_path"] . $file;
        }
        if(!$this->par["pbx_type"]){
$file=$key.".mp3";
        $url=$this->par["cdr_path"]."/".$file;
    
        }elseif ($this->par["pbx_type"] == "freepbx") {
            $path = urlencode($this->_encrypt($file));
            $url = $this->_get_url("/admin/config.php?display=cdr&action=cdr_audio&cdr_file=#name#", array(
                "#name#" => $path
            ));
            $post = 'username=' . $this->par["pbx_admin_user"] . '&password=' . $this->par["pbx_admin_pass"];
        } elseif ($this->par["pbx_type"] == "elastix") {
            $post = 'input_user=' . $this->par["pbx_admin_user"] . '&input_pass=' . $this->par["pbx_admin_pass"] . "&submit_login=" . GetMessage("ISTLINE_CALL_OTPRAVITQ");
            $url = $this->_get_url("/index.php?menu=monitoring&action=download&id=#id#&namefile=#name#&rawmode=yes", array(
                "#id#" => $key,
                "#name#" => basename($file)
            ));
        }
        $ch = curl_init();
	$this->debug($url);
        if($this->par["pbx_admin_pass"]){
        curl_setopt($ch, CURLOPT_URL, $this->par["pbx_protocol"] . "://" . $this->par["pbx_ip"] . $this->par["pbx_auth_url"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $_SERVER['DOCUMENT_ROOT'] . "/cooc.txt");
        curl_setopt($ch, CURLOPT_COOKIEFILE, $_SERVER['DOCUMENT_ROOT'] . "/cooc.txt");
        if ($this->par["pbx_protocol"] == "https") {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $result = curl_exec($ch);
}
        curl_setopt($ch, CURLOPT_URL, $this->par["pbx_protocol"] . "://" . $this->par["pbx_ip"] . $url);
        curl_setopt($ch, CURLOPT_REFERER, $this->par["pbx_protocol"] . "://" . $this->par["pbx_ip"] . $this->par["pbx_auth_url"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, false);
if(!$file){
$file=$key.".wav";
}
        $wavname = basename($file);
        $extension = strtolower(substr(strrchr($wavname, "."), 1));
        $file_resource = fopen($this->tmpDIR . $wavname, 'w+b');
        curl_setopt($ch, CURLOPT_FILE, $file_resource);
        $result = curl_exec($ch);
        curl_close($ch);
        fclose($file_resource);
        if ($extension == 'wav') {
            $mp3name = preg_replace("|.wav|", ".mp3", $wavname);
            if (PHP_OS == "WINNT") {
                $res = exec("cmd /C " . $this->par["crm_call_lame"] . "\lame.exe -V 9 --scale 3 " . $this->tmpDIR . $wavname . " " . $this->tmpDIR . $mp3name, $output, $result);
            } else {
                $rr=exec("lame -V 9 --scale 3 " . $this->tmpDIR . $wavname . " " . $this->tmpDIR . $mp3name, $output, $result);
            }
//print_r(array($rr,$output,$result));
            unlink($this->tmpDIR . $wavname);
        } else {
            $mp3name = $wavname;
        }
        if (file_exists($this->tmpDIR . $mp3name)) {
		$this->debug("файл скопирован");

            return $this->tmpDIR . $mp3name;
        } else {
            return false;
        }
    }

    public function saveFileToActiv($actID, $file)
    {
        $arFile = \CFile::MakeFileArray($file);
        $arFile = array_merge($arFile, array('MODULE_ID' => 'istline.call'));
        $arFile['ORIGINAL_NAME'] = $arFile['name'];
        $IDFile = \CFile::SaveFile($arFile, "istlineCall");
        $arFields['STORAGE_TYPE_ID'] = 1;
        $arFields['STORAGE_ELEMENT_IDS'] = array($IDFile);
        $arFields['COMPLETED'] = 'Y';
        $m = \CCrmActivity::Update($actID, $arFields, false);
        unlink($file);
    }


    public function dump_events($ecode, $data, $server, $port, $params = array())
    {
        global $callID, $files;
        $date_now = date('Y-m-d');
        $time_now = date('H:i:s');
        $this->_normalize_key($data);
        if ($callID && ($data["uniqueid"] == $callID || $data["linkedid"] == $callID)||$data["destuniqueid"]==$callID) {
            set_time_limit(60);

if(in_array($data["event"],array("VarSet","RTCPSent","PeerStatus","RTCPReceived"))){
	return;
}
$this->debug($data);

        if ($data["eventname"] == "CHAN_START") {
                $card = new \Istline\Call\CallCard($this);
                $card->showUser($this->getManagersByInnerPhone($data["calleridnum"]), $params);
                $this->addActiveManager($data["calleridnum"], $callID);
            }
            if ($data["eventname"] == "ANSWER" && $data["context"] != "macro-dial") {
                $card = new \Istline\Call\CallCard($this);
                foreach ($this->getActiveManagers() as $phone => $cid) {
                    if ($phone != $data["calleridnum"] && $cid == $callID) {
                        $arManagID = $this->getManagersByInnerPhone($phone);
                        $card->closeCard($arManagID[0], $params);
                        $this->removeActiveManagers($phone);
                    } else {
                        $arManagID = $this->getManagersByInnerPhone($phone);
                        $arFields['RESPONSIBLE_ID'] = $arManagID[0];
                        $m = \CCrmActivity::Update($params["ACT"], $arFields, false);
                    }
                }
            }
            if ($data["eventname"] == "APP_END" && $data["application"] == "MixMonitor" && strlen($data["appdata"]) > 0) {
                $tmpFarr = explode(",", $data["appdata"]);
                if (!empty($files) && !in_array($tmpFarr[0], $files)) {
                    $files[$callID] = $tmpFarr[0];
                }
            } elseif ($data["variable"] == "MIXMONITOR_FILENAME") {
                if (!empty($files) && !in_array($data["value"], $files)) {
                    $files[$callID] = $data["value"];
                }
            } elseif ($data["event"] == "Newexten" && $data["application"] == "MixMonitor") {
                $tmpFarr = explode(",", $data["appdata"]);
                if (!empty($files) && !in_array($tmpFarr[0], $files)) {
                    $files[$callID] = $tmpFarr[0];
                }
            }
            if ($data["event"] == "VarSet" && $data["variable"] == "MP3FILE") {
                if (!empty($files) && !in_array($data["value"], $files)) {
                    $files[$callID] = $data["value"];
                }
            }
if($data["event"]=="Dial"&&$data["subevent"]=="Begin"){
                $card = new \Istline\Call\CallCard($this);
                $card->showUser($this->getManagersByInnerPhone($data["dialstring"]), $params);
                $this->addActiveManager($data["dialstring"], $callID);

}        
if($data["event"]=="VarSet"&&$data["variable"]=="BRIDGEPEER"){
                $numb_arr=preg_match("|.*\/(\d{".$this->par["pbx_inner_len"]."})|",$data["value"],$mat);
                $numb=$mat[1];
                $card = new \Istline\Call\CallCard($this);
                foreach ($this->getActiveManagers() as $phone => $cid) {
                    if ($phone != $numb && $cid == $callID) {
                        $arManagID = $this->getManagersByInnerPhone($phone);
                        $card->closeCard($arManagID[0], $params);
                        $this->removeActiveManagers($phone);
                    } else {
                        $arManagID = $this->getManagersByInnerPhone($phone);
                        $arFields['RESPONSIBLE_ID'] = $arManagID[0];
                        $m = \CCrmActivity::Update($params["ACT"], $arFields, false);
                    }
                }
}
        }
        if (isset($files[$callID]) && !empty($files[$callID]) && $this->waitFile) {
            $this->waitFile = false;
            $phone = $_REQUEST["targetN"] ? $_REQUEST["targetN"] : $_REQUEST["phonenumber"];
            $data = "ACT=" . $params["ACT"] . ",FILE=" . $files[$callID] . ",PHONE=" . $phone;
            if (!file_exists($this->tmpDIR . 'calls/')) {
                mkdir($this->tmpDIR . 'calls/');
            }
            file_put_contents($this->tmpDIR . 'calls/' . $callID, $data);
            global $APPLICATION;
            $APPLICATION->RestartBuffer();
            $f = $this->runInBackground(dirname(__FILE__) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "tools" . DIRECTORY_SEPARATOR . "monitor.php");
            exit(0);
        }
    }

    public function addLead($owner, $phone)
    {
        global $USER;
        $USER->Authorize(1);
        if (strlen($phone) == 10) {
            $phone = '8' . $phone;
        }
        $CCrmLead = new \CCrmLead();
        $arFields = array(
            "ASSIGNED_BY" => $owner,
            "ASSIGNED_BY_ID" => $owner,
            "CREATED_BY" => 1,
            "CREATED_BY_ID" => 1,
            "MODIFY_BY" => 1,
            "MODIFY_BY_ID" => 1,
            "TITLE" => GetMessage("ISTLINE_CALL_NOVYY_LID_IZ_VHODASE"),
            "SOURCE_ID" => GetMessage("ISTLINE_CALL_NOVYY_LID_IZ_VHODASE"),
            "SOURCE_DESCRIPTION" => GetMessage("ISTLINE_CALL_NOVYY_LID_IZ_VHODASE"),
            "COMMENTS" => "",
            "STATUS_ID" => "",
            "NAME" => "",
            "SECON_NAME" => "",
            "LAST_NAME" => "",
            "COMPANY_TITLE" => "",
            "FM" => array(
                "PHONE" => array(
                    "n1" => array(
                        "VALUE" => $phone,
                        "VALUE_TYPE" => "WORK"
                    )
                )
            )

        );
        $ID = $CCrmLead->Add($arFields, true, array('REGISTER_SONET_EVENT' => true));
        $USER->Logout();

        return $this->getEntityListByPhone($phone);
    }

    public function getEntAndAddCall($entList, $phone, $incoming = false, $userID = 1)
    {
        global $DB;
        if (isset($entList['CONTACT'])) {
            $ownerType = \CCrmOwnerType::Contact;
            $ownerID = $entList['CONTACT'][0]['ID'];
        } else if (isset($entList['COMPANY'])) {
            $ownerType = \CCrmOwnerType::Company;
            $ownerID = $entList['COMPANY'][0]['ID'];
        } else if (isset($entList['LEAD'])) {
            $ownerType = \CCrmOwnerType::Lead;
            $ownerID = $entList['LEAD'][0]['ID'];
        }
        $arFields = array(
            "ownerID" => $ownerID,
            "ownerType" => $ownerType,
            "USER_ID" => $userID,
            "USER_NAME" => "",
            "PHONE_NUMBER" => $phone,
            "START_TIME" => date($DB->DateFormatToPHP(\CLang::GetDateFormat("FULL"))),
        );
        if ($incoming) {
            $arFields["INCOMING"] = 1;
        }
        return $this->AddCall($arFields);
    }

    public function saveInfo($uid, $data)
    {
        if (!file_exists($this->tmpDIR . 'calls/')) {
            mkdir($this->tmpDIR . 'calls/');
        }
        $t = file_put_contents($this->tmpDIR . 'calls/' . $uid, $data);
    }
}

