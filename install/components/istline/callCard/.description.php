<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("ISTLINE_CALL_KARTOCKA_ZVONKA"),
	"DESCRIPTION" => GetMessage("ISTLINE_CALL_FORMIRUET_I_VYVODIT"),
	"ICON" => "/images/icon.gif",
	"SORT" => 10,
	"CACHE_PATH" => "Y",
	"PATH" => array(
		"ID" => GetMessage("ISTLINE_CALL_UPRAVLENIE_ZVONKAMI"),
	),
	"COMPLEX" => "N",
);

?>