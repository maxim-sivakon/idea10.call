<?
IncludeModuleLangFile(__FILE__);

CModule::AddAutoloadClasses(
    "istline.call",
    array(
        "callPullShema" => "lib/callPullShema.php",
        "CallCard" => "lib/callcard.php",
        "Agi_AsteriskManager" => "lib/agi_asteriskmanager.php",
        "CMyAGI_AsteriskManager" => "lib/cmyagi_asteriskmanager.php",
    )
);

class CIstlineCall
{

    public static function addCallScript()
    {
        if (CModule::IncludeModule('istline.call')) {
            if (!defined("ADMIN_SECTION") || !ADMIN_SECTION) {
                CJSCore::RegisterExt('istlineCall', array(
                    'js' => '/bitrix/js/istlineCall/call.js',
                    'rel' => array("jquery")
                ));
                CJSCore::Init(array("istlineCall"));
            }
        }

    }

}
