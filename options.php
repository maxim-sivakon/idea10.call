<?
$module_id = "istline.call";
$RIGHT = $APPLICATION->GetGroupRight($module_id);
if ($RIGHT >= "R") :

    IncludeModuleLangFile($_SERVER[ "DOCUMENT_ROOT" ].BX_ROOT."/modules/main/options.php");
    IncludeModuleLangFile(__FILE__);

    $aSubTabs = [];
    $subTabControl = [];
    $dbCountries = [
        ["ID" => "RU", "NAME" => "Россия"],
        ["ID" => "BY", "NAME" => "Беларусь"],
        ["ID" => "KZ", "NAME" => "Казахстан"],
        ["ID" => "UZ", "NAME" => "Узбекистан"],
        ["ID" => "KG", "NAME" => "Киргизия"],
        ["ID" => "TR", "NAME" => "Турция"],
    ];

    foreach ($dbCountries as $key => $country) {
        $aSubTabs[] = [
            "DIV"   => "opt_country_".$country[ "ID" ], "TAB" => "(".$country[ "ID" ].") ".$country[ "NAME" ],
            'TITLE' => ''
        ];
        $arAllOptions[ $country[ "ID" ] ] = [
            [
                "pbx_type_".$country[ 'ID' ], GetMessage("ISTLINE_CALL_VASA_ATS"),
                ["selectbox", ["clear" => "Clear", "freepbx" => "FREE PBX", "elastix" => "Elastix"]]
            ],
            ["pbx_prefix_".$country[ 'ID' ], GetMessage("ISTLINE_CALL_PREFIKS_VYHODA_NA_LI"), ["text", 15]],
            [
                "pbx_protocol_".$country[ 'ID' ], GetMessage("ISTLINE_CALL_PROTOKOL_VEB_INTERFE"),
                ["selectbox", ["http" => "http", "https" => "https"]]
            ],
            ["pbx_ip_".$country[ 'ID' ], "IP ".GetMessage("ISTLINE_CALL_ADRES_SERVERA_ATS"), ["text", 15]],
            ["pbx_auth_url_".$country[ 'ID' ], GetMessage("ISTLINE_CALL_ADRES_DLA_AVTORIZACI"), ["text", 15]],
            ["pbx_admin_user_".$country[ 'ID' ], GetMessage("ISTLINE_CALL_LOGIN_ADMINISTRATORA"), ["text", 15]],
            ["pbx_admin_pass_".$country[ 'ID' ], GetMessage("ISTLINE_CALL_PAROLQ_ADMINISTRATOR"), ["text", 15]],
            ["pbx_ami_user_".$country[ 'ID' ], GetMessage("ISTLINE_CALL_LOGIN_POLQZOVATELA"), ["text", 15]],
            ["pbx_ami_pass_".$country[ 'ID' ], GetMessage("ISTLINE_CALL_PAROLQ_POLQZOVATELA"), ["text", 15]],
            ["pbx_ami_port_".$country[ 'ID' ], GetMessage("ISTLINE_CALL_PORT_DLA_PODKLUCENIA"), ["text", 15]],
            ["cdr_path_".$country[ 'ID' ], GetMessage("ISTLINE_CALL_PUTQ_K_PAPKE_S_ZAPIS"), ["text", 15]],
            ["cdr_salt_".$country[ 'ID' ], GetMessage("ISTLINE_CALL_PAROLQ_SIFROVANIA_PU"), ["text", 15]],
            ["crm_manager_".$country[ 'ID' ], GetMessage("ISTLINE_CALL_CRM_MANGER"), ["text:user", 15]],
            [
                "crm_call_variant_".$country[ 'ID' ], GetMessage("ISTLINE_CALL_VARIANT_RASPREDELENI"), [
                "selectbox", [
                    "ringall" => GetMessage("ISTLINE_CALL_VSEM_SRAZU"),
                    "turns"   => GetMessage("ISTLINE_CALL_PO_OCEREDI"),
                    "random"  => GetMessage("ISTLINE_CALL_SLUCAYNO")
                ]
            ]
            ],
            ["crm_call_delay_".$country[ 'ID' ], GetMessage("ISTLINE_CALL_DELAY"), ["text", 15]],
            ["crm_call_lame_".$country[ 'ID' ], GetMessage("ISTLINE_CALL_LAME"), ["text", 15]],
            ["crm_call_fix_all_".$country[ 'ID' ], GetMessage("ISTLINE_CALL_FIKSIROVATQ_ISHODASI"), ["checkbox", 15]],
        ];
    }
    $subTabControl = new CAdminViewTabControl("subTabControl", $aSubTabs);

    CModule::IncludeModule('istline.call');
    $c = new \Istline\Call\Call();
    $dids = $c->getTruncs();
    $arTruncs = [];
    foreach ($dids as $did) {
        $arTruncs[] = [
            "crm_did_use_default_manager_".$did, GetMessage("ISTLINE_CALL_VKLUCITQ_DEFOLTNYH_M").$did, ["checkbox", 15]
        ];
        $arTruncs[] = ["mail_".$did, GetMessage("ISTLINE_CALL_GOLOSOVAA_POCTA_DLA").$did, ["text", 15]];
        $arTruncs[] = ["crm_did_manager_".$did, GetMessage("ISTLINE_CALL_CRM_DID_MANGER")." ".$did, ["text:user", 15]];
    }

    $aTabs = [
        [
            "DIV"   => "edit1", "TAB" => GetMessage("MAIN_TAB_SET"), "ICON" => "perfmon_settings",
            "TITLE" => GetMessage("MAIN_TAB_TITLE_SET")
        ],
        [
            "DIV"   => "edit2", "TAB" => GetMessage("ISTLINE_CALL_VHODASIE_LINII"), "ICON" => "perfmon_settings",
            "TITLE" => GetMessage("ISTLINE_CALL_VHODASIE_LINII")
        ],
        [
            "DIV"   => "edit3", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "ICON" => "perfmon_settings",
            "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS")
        ],
    ];

    $tabControl = new CAdminTabControl("tabControl", $aTabs);

    CModule::IncludeModule($module_id);

    if ($REQUEST_METHOD == "POST" && strlen($Update.$Apply.$RestoreDefaults) > 0 && $RIGHT == "W" && check_bitrix_sessid()) {
        if (strlen($RestoreDefaults) > 0) {
            COption::RemoveOption("istline.call");
        } else {
            foreach ($dbCountries as $key => $country) {

                foreach ($arAllOptions[ $country[ "ID" ] ] as $arOption) {
                    $name = $arOption[ 0 ];
                    $val = $_REQUEST[ $name ];
                    if ($arOption[ 2 ][ 0 ] == "checkbox" && $val != "Y") {
                        $val = "N";
                    }
                    if (is_array($val)) {
                        $val = array_diff($val, ['']);
                        $val = serialize($val);
                    }
                    COption::SetOptionString("istline.call", $name, $val, $arOption[ 1 ]);
                }
            }
            foreach ($arTruncs as $arOption) {
                $name = $arOption[ 0 ];
                $val = $_REQUEST[ $name ];
                if ($arOption[ 2 ][ 0 ] == "checkbox" && $val != "Y") {
                    $val = "N";
                }
                if (is_array($val)) {
                    $val = array_diff($val, ['']);
                    $val = serialize($val);
                }
                COption::SetOptionString("istline.call", $name, $val, $arOption[ 1 ]);
            }
        }
        ob_start();
        $Update = $Update.$Apply;
        require_once($_SERVER[ "DOCUMENT_ROOT" ]."/bitrix/modules/main/admin/group_rights.php");
        ob_end_clean();

        if (strlen($_REQUEST[ "back_url_settings" ]) > 0) {
            if ((strlen($Apply) > 0) || (strlen($RestoreDefaults) > 0)) {
                LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($module_id)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST[ "back_url_settings" ])."&".$tabControl->ActiveTabParam());
            } else {
                LocalRedirect($_REQUEST[ "back_url_settings" ]);
            }
        } else {
            LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($module_id)."&lang=".urlencode(LANGUAGE_ID)."&".$tabControl->ActiveTabParam());
        }
    }

    ?>
    <form method="post"
          action="<? echo $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($module_id) ?>&amp;lang=<?= LANGUAGE_ID ?>">
        <?
        $tabControl->Begin();
        $tabControl->BeginNextTab(); ?>

        <tr>
            <td valign="top" colspan="2" align="center">
                <?
                $subTabControl->Begin();
                foreach ($dbCountries as $country) {
                    $subTabControl->BeginNextTab();

                    $arNotes = [];

                    ?>
                    <table>
                        <?
                        foreach ($arAllOptions[ $country[ "ID" ] ] as $arOption) {
                            $val = COption::GetOptionString("istline.call", $arOption[ 0 ]);
                            $type = $arOption[ 2 ];
                            if ($type[ 0 ] == "text:user") {
                                $val = unserialize($val);
                            }
                            if (isset($arOption[ 3 ])) {
                                $arNotes[] = $arOption[ 3 ];
                            }
                            ?>
                            <tr>
                                <td width="40%" nowrap <? if ($type[ 0 ] == "textarea")
                                    echo 'class="adm-detail-valign-top"' ?>>
                                    <? if (isset($arOption[ 3 ])): ?>
                                        <span class="required"><sup><? echo count($arNotes) ?></sup></span>
                                    <? endif; ?>
                                    <label for="<? echo htmlspecialcharsbx($arOption[ 0 ]) ?>"><? echo $arOption[ 1 ] ?>
                                        :</label>
                                <td width="60%">
                                    <? if ($type[ 0 ] == "checkbox"): ?>
                                        <input
                                                type="checkbox"
                                                name="<? echo htmlspecialcharsbx($arOption[ 0 ]) ?>"
                                                id="<? echo htmlspecialcharsbx($arOption[ 0 ]) ?>"
                                                value="Y"<? if ($val == "Y") {
                                            echo " checked";
                                        } ?>>
                                    <? elseif ($type[ 0 ] == "text"): ?>
                                        <input
                                                type="text"
                                                size="<? echo $type[ 1 ] ?>"
                                                maxlength="255"
                                                value="<? echo htmlspecialcharsbx($val) ?>"
                                                name="<? echo htmlspecialcharsbx($arOption[ 0 ]) ?>"
                                                id="<? echo htmlspecialcharsbx($arOption[ 0 ]) ?>">
                                    <? elseif ($type[ 0 ] == "text:user"): ?>
                                        <?
                                        $arUser = [];
                                        $val = empty($val) ? $val : array_diff($val, ['']);
                                        foreach ($val as $id) {
                                            $us = $USER->GetByID($id);
                                            $user = $us->GetNext();
                                            $arUser[] = $user[ "LAST_NAME" ]." ".$user[ "NAME" ]." [".$id."]";
                                        }
                                        $GLOBALS[ "APPLICATION" ]->IncludeComponent('bitrix:intranet.user.selector', '',
                                            [
                                                'INPUT_NAME'            => $arOption[ 0 ],
                                                'INPUT_NAME_STRING'     => $arOption[ 0 ]."_string",
                                                'INPUT_NAME_SUSPICIOUS' => $arOption[ 0 ]."_suspicious",
                                                'TEXTAREA_MIN_HEIGHT'   => 50,
                                                'TEXTAREA_MAX_HEIGHT'   => 200,
                                                'INPUT_VALUE_STRING'    => implode("\n", $arUser),
                                                'EXTERNAL'              => 'A',
                                                'SOCNET_GROUP_ID'       => ""
                                            ]
                                        );

                                        ?>
                                    <?
                                    elseif ($type[ 0 ] == "textarea"): ?>
                                        <textarea
                                                rows="<? echo $type[ 1 ] ?>"
                                                cols="<? echo $type[ 2 ] ?>"
                                                name="<? echo htmlspecialcharsbx($arOption[ 0 ]) ?>"
                                                id="<? echo htmlspecialcharsbx($arOption[ 0 ]) ?>"
                                        ><? echo htmlspecialcharsbx($val) ?></textarea>
                                    <? elseif ($type[ 0 ] == "selectbox"): ?>
                                        <select name="<? echo htmlspecialcharsbx($arOption[ 0 ]) ?>">
                                            <? foreach ($type[ 1 ] as $optionValue => $optionDisplay) {
                                                ?>
                                                <option value="<? echo $optionValue ?>"<? if ($val == $optionValue)
                                                    echo " selected" ?>><? echo htmlspecialcharsbx($optionDisplay) ?></option><?
                                            } ?>
                                        </select>
                                    <? elseif ($type[ 0 ] == "statichtml"): ?>
                                        <? echo $panel; ?>
                                    <? endif ?>

                                </td>
                            </tr>
                        <? } ?>
                    </table>
                    <?
                    //unset($arNotes);
                }
                $subTabControl->End();
                ?>
            </td>
        </tr>

        <?
        $tabControl->BeginNextTab();
        $arNotes = []; ?>
        <? if (isset($c->err) && !empty($c->err)) { ?>
            <tr>
                <td>
                    <? foreach ($c->err as $err) { ?>
                        <div class="error"><?= $err ?></div>
                    <? } ?>
                </td>
            </tr>
        <? } ?>
        <?
        foreach ($arTruncs as $arOption):
            $val = COption::GetOptionString("istline.call", $arOption[ 0 ]);
            $type = $arOption[ 2 ];
            if ($type[ 0 ] == "text:user") {
                $val = unserialize($val);
            }
            if (isset($arOption[ 3 ])) {
                $arNotes[] = $arOption[ 3 ];
            }
            ?>
            <tr>
                <td width="40%" nowrap <? if ($type[ 0 ] == "textarea")
                    echo 'class="adm-detail-valign-top"' ?>>
                    <? if (isset($arOption[ 3 ])): ?>
                        <span class="required"><sup><? echo count($arNotes) ?></sup></span>
                    <? endif; ?>
                    <label for="<? echo htmlspecialcharsbx($arOption[ 0 ]) ?>"><? echo $arOption[ 1 ] ?>
                        :</label>
                <td width="60%">
                    <? if ($type[ 0 ] == "checkbox"): ?>
                        <input
                                type="checkbox"
                                name="<? echo htmlspecialcharsbx($arOption[ 0 ]) ?>"
                                id="<? echo htmlspecialcharsbx($arOption[ 0 ]) ?>"
                                value="Y"<? if ($val == "Y") {
                            echo " checked";
                        } ?>>
                    <? elseif ($type[ 0 ] == "text"): ?>
                        <input
                                type="text"
                                size="<? echo $type[ 1 ] ?>"
                                maxlength="255"
                                value="<? echo htmlspecialcharsbx($val) ?>"
                                name="<? echo htmlspecialcharsbx($arOption[ 0 ]) ?>"
                                id="<? echo htmlspecialcharsbx($arOption[ 0 ]) ?>">
                    <? elseif ($type[ 0 ] == "text:user"): ?>
                        <?
                        $arUser = [];
                        $val = array_diff($val, ['']);
                        foreach ($val as $id) {
                            $us = $USER->GetByID($id);
                            $user = $us->GetNext();
                            $arUser[] = $user[ "LAST_NAME" ]." ".$user[ "NAME" ]." [".$id."]";
                        }
                        $GLOBALS[ "APPLICATION" ]->IncludeComponent('bitrix:intranet.user.selector', '', [
                                'INPUT_NAME'            => $arOption[ 0 ],
                                'INPUT_NAME_STRING'     => $arOption[ 0 ]."_string",
                                'INPUT_NAME_SUSPICIOUS' => $arOption[ 0 ]."_suspicious",
                                'TEXTAREA_MIN_HEIGHT'   => 50,
                                'TEXTAREA_MAX_HEIGHT'   => 200,
                                'INPUT_VALUE_STRING'    => implode("\n", $arUser),
                                'EXTERNAL'              => 'A',
                                'SOCNET_GROUP_ID'       => ""
                            ]
                        );

                        ?>
                    <?
                    elseif ($type[ 0 ] == "textarea"): ?>
                        <textarea
                                rows="<? echo $type[ 1 ] ?>"
                                cols="<? echo $type[ 2 ] ?>"
                                name="<? echo htmlspecialcharsbx($arOption[ 0 ]) ?>"
                                id="<? echo htmlspecialcharsbx($arOption[ 0 ]) ?>"
                        ><? echo htmlspecialcharsbx($val) ?></textarea>
                    <? elseif ($type[ 0 ] == "selectbox"): ?>
                        <select name="<? echo htmlspecialcharsbx($arOption[ 0 ]) ?>">
                            <? foreach ($type[ 1 ] as $optionValue => $optionDisplay) {
                                ?>
                                <option value="<? echo $optionValue ?>"<? if ($val == $optionValue)
                                    echo " selected" ?>><? echo htmlspecialcharsbx($optionDisplay) ?></option><?
                            } ?>
                        </select>
                    <? elseif ($type[ 0 ] == "statichtml"): ?>
                        <? echo $panel; ?>
                    <? endif ?>

                </td>
            </tr>


        <? endforeach ?>

        <? $tabControl->BeginNextTab(); ?>
        <? require_once($_SERVER[ "DOCUMENT_ROOT" ]."/bitrix/modules/main/admin/group_rights.php"); ?>
        <? $tabControl->Buttons(); ?>
        <input <? if ($RIGHT < "W")
            echo "disabled" ?> type="submit" name="Update" value="<?= GetMessage("MAIN_SAVE") ?>"
                               title="<?= GetMessage("MAIN_OPT_SAVE_TITLE") ?>" class="adm-btn-save">
        <input <? if ($RIGHT < "W")
            echo "disabled" ?> type="submit" name="Apply" value="<?= GetMessage("MAIN_OPT_APPLY") ?>"
                               title="<?= GetMessage("MAIN_OPT_APPLY_TITLE") ?>">
        <? if (strlen($_REQUEST[ "back_url_settings" ]) > 0): ?>
            <input
                <? if ($RIGHT < "W")
                    echo "disabled" ?>
                    type="button"
                    name="Cancel"
                    value="<?= GetMessage("MAIN_OPT_CANCEL") ?>"
                    title="<?= GetMessage("MAIN_OPT_CANCEL_TITLE") ?>"
                    onclick="window.location='<? echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST[ "back_url_settings" ])) ?>'"
            >
            <input
                    type="hidden"
                    name="back_url_settings"
                    value="<?= htmlspecialcharsbx($_REQUEST[ "back_url_settings" ]) ?>"
            >
        <? endif ?>
        <?= bitrix_sessid_post(); ?>
        <? $tabControl->End(); ?>
    </form>
    <?
    if (!empty($arNotes)) {
        echo BeginNote();
        foreach ($arNotes as $i => $str) {
            ?><span class="required"><sup><? echo $i + 1 ?></sup></span><? echo $str ?><br><?
        }
        echo EndNote();
    }
    ?>
<? endif; ?>
