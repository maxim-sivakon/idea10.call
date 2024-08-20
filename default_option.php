<?php

$dbCountries = [
    ["ID" => "RU", "NAME" => "Россия"],
    ["ID" => "BY", "NAME" => "Беларусь"],
    ["ID" => "KZ", "NAME" => "Казахстан"],
    ["ID" => "UZ", "NAME" => "Узбекистан"],
    ["ID" => "KG", "NAME" => "Киргизия"],
    ["ID" => "TR", "NAME" => "Турция"],
];
$istline_call_default_option = [];

foreach ($dbCountries as $key => $country) {
    $istline_call_default_option += [
        "pbx_type_".$country[ "ID" ]         => "clear",
        "pbx_prefix_".$country[ "ID" ]       => "",
        "pbx_protocol_".$country[ "ID" ]     => "http",
        "pbx_ip_".$country[ "ID" ]           => "192.168.2.10",
        "pbx_auth_url_".$country[ "ID" ]     => "/",
        "pbx_admin_user_".$country[ "ID" ]   => "admin",
        "pbx_admin_pass_".$country[ "ID" ]   => "demo_pass",
        "pbx_ami_user_".$country[ "ID" ]     => "bitrix",
        "pbx_ami_pass_".$country[ "ID" ]     => "demo_pass",
        "pbx_ami_port_".$country[ "ID" ]     => "5038",
        "cdr_path_".$country[ "ID" ]         => "/mp3",
        //"cdr_salt_".$country[ "ID" ]         => "TheWindCriesMary",
        "cdr_salt_".$country[ "ID" ]         => "",
        "crm_call_variant_".$country[ "ID" ] => "turns",
        "crm_call_delay_".$country[ "ID" ]   => "15",
        "crm_call_lame_".$country[ "ID" ]    => "E:\OpenServer",
        "crm_call_fix_all_".$country[ "ID" ] => "",
    ];
}