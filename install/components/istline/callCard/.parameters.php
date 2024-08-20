<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentParameters = array(
	"GROUPS" => array(
	),
	"PARAMETERS" => array(
        "CID" => array(
            "PARENT" => "BASE",
            "NAME" => GetMessage("ISTLINE_CALL_VHODASIY_NOMER"),
            "TYPE" => "STRING",
        ),
        "USER_ID" => array(
            "PARENT" => "BASE",
            "NAME" => "ID ".GetMessage("ISTLINE_CALL_POLQZOVATELA"),
            "TYPE" => "STRING",
        ),
        "ACT_ID" => array(
            "PARENT" => "BASE",
            "NAME" => "ID ".GetMessage("ISTLINE_CALL_KARTOCKI_ZVONKA"),
            "TYPE" => "STRING",
        ),
	),
);
?>