<?
if(file_exists($_SERVER["DOCUMENT_ROOT"]."/local/modules/istline.call/tools/istlineCall.php")){
    require($_SERVER["DOCUMENT_ROOT"]."/local/modules/istline.call/tools/istlineCall.php");
}else {
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/istline.call/tools/istlineCall.php");
}