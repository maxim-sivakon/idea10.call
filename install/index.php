<?
IncludeModuleLangFile(__FILE__);

if (class_exists("istline_call"))
    return;

class istline_call extends CModule
{
    var $MODULE_ID = "istline.call";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;

    function __construct()
    {
        $arModuleVersion = array();

        include(dirname(__FILE__) . "/version.php");

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

        $this->MODULE_NAME = GetMessage("ISTLINE_CALL_INSTALL_NAME");
        $this->MODULE_DESCRIPTION = GetMessage("ISTLINE_CALL_INSTALL_DESCRIPTION");

        $this->PARTNER_NAME = GetMessage("ISTLINE_CALL_PARTNER_NAME");
        $this->PARTNER_URI = GetMessage("ISTLINE_CALL_PARTNER_URI");
    }


    function InstallDB()
    {
        RegisterModuleDependences( "main", "OnBeforeEndBufferContent", $this->MODULE_ID, "CIstlineCall", "addCallScript" ) ;
        RegisterModuleDependences("pull", "OnGetDependentModule", $this->MODULE_ID, "callPullShema", "OnGetDependentModule" );
        RegisterModule("istline.call");
        return true;
    }

    function UnInstallDB()
    {
        UnRegisterModuleDependences( "main", "OnBeforeEndBufferContent", $this->MODULE_ID, "CIstlineCall", "addCallScript" ) ;
        UnRegisterModuleDependences("pull", "OnGetDependentModule", $this->MODULE_ID, "callPullShema", "OnGetDependentModule" );
        UnRegisterModule("istline.call");
        return true;
    }

    function InstallFiles()
    {
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/istline.call/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js", true, true);
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/istline.call/install/tools", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools", true, true);
        return true;
    }

    function UnInstallFiles()
    {
        return true;
    }

    function DoInstall()
    {
        $this->InstallFiles();
        $this->InstallDB();
    }

    function DoUninstall()
    {
        $this->UnInstallDB();
        $this->UnInstallFiles();
    }
}