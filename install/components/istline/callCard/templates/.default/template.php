<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>

<? if ($arResult["CID"]) { ?>
    <script>
        if ($("#istlineCallCss").length < 1) {
            $("head").append($("<link id='istlineCallCss' href='<?=$templateFolder?>/style.css' type='text/css' media='screen' rel='stylesheet'/>"));
        }
        $(document).ready(function () {
            $(".calllayer .but.close").click(function () {
                $(".calllayer").remove();
            })
            $(".footer .butt.save").click(function(){
                data={
                    text:$(".wrp-call textarea").val(),
                    action:"end_call",
                    act_id:$(".wrp-call").attr("id")
                }
                console.log(data);
                istlineCall.action(data);
                $(".calllayer").remove();
            })
        })
    </script>
    <div class="calllayer">
        <div class="wrp-call" id="act<?=$arParams["ACT_ID"]?>">
            <div class="header">
                <div class="logocall">
                    <img src="<?= $templateFolder ?>/img/logocall.png">
                </div>
                <div class="cid">
                    <?=GetMessage("ISTLINE_CALL_VHODASIY_ZVONOK_OT")?><?= $arResult["CID"] ?>
                </div>
                <div class="but close">
                    <img src="<?= $templateFolder ?>/img/close.png">
                </div>
            </div>
            <div class="shadowblock"></div>
            <div class="contacts">
                <? $count = 0 ?>
                <? foreach ($arResult["TYPE"] as $type => $elems) { ?>
                    <? foreach ($elems as $elem) { ?>
                        <? if ($count > 0) { ?>
                            <div class="separator">
                                <div class="bordered left"></div>
                                <div class="nobordered"></div>
                                <div class="bordered right"></div>
                            </div>
                        <? } ?>
                        <? $count++ ?>
                        <div class="itemwrap">
                            <div class="entity-wrp">
                                <div class="entitylogo">
                                    <img src="<?= $templateFolder ?>/img/<?= $type ?>.png">
                                </div>
                                <div class="contactdata">
                                    <?
                                    switch ($type) {
                                        case "COMPANY":
                                            $name = GetMessage("ISTLINE_CALL_KOMPANIA");
                                            break;
                                        case "CONTACT":
                                            $name = GetMessage("ISTLINE_CALL_KONTAKT");
                                            break;
                                        case "LEAD":
                                            $name = GetMessage("ISTLINE_CALL_LID");
                                            break;
                                    }
                                    ?>
                                    <div class="title"><b><?= $name ?>:</b> <a href="<?=$elem["SHOW_URL"]?>" target="_blank" ><?= $elem["TITLE"] ?></a></div>
                                    <div class="phone"><b><?=GetMessage("ISTLINE_CALL_TELEFON_Y")?></b> <?= implode(",", $elem["PHONE"]) ?></div>
                                    <div class="mail"><b><?=GetMessage("ISTLINE_CALL_POCTA")?></b> <?= implode(",", $elem["MAIL"]) ?></div>
                                    <div class="manager"><b><?=GetMessage("ISTLINE_CALL_MENEDJER")?></b> <?= $elem["ASSIGNED"] ?></div>
                                </div>
                            </div>
                            <div class="comm-wrp">
                                <? foreach ($elem["COMMS"] as $commtype => $comms) { ?>
                                    <div class="commtype">
                                        <div class="commtypetitle"><?= $arResult["COMMTITLE"][$commtype] ?></div>
                                        <div class="listcomm">
                                            <? foreach ($comms as $comm) { ?>
                                                <div class="itemcomm">
                                                    <div
                                                        class="commdata"><?= ConvertDateTime($comm["DATE"], "DD-MM-YYYY", "ru"); ?></div>
                                                    <div class="commtitle"><b><?= $comm["TITLE"] ?></b></div>
                                                </div>
                                            <? } ?>
                                        </div>
                                    </div>
                                <? } ?>
                            </div>
                        </div>
                    <? } ?>
                <? } ?>
            </div>
            <div class="shadowblock"></div>
            <div class="comments">
                <textarea name="" id="" cols="30" rows="10"></textarea>
            </div>
            <div class="shadowblock"></div>
            <div class="footer">
                <div class="butt save"><?=GetMessage("ISTLINE_CALL_SOHRANITQ")?></div>
            </div>
        </div>
    </div>
<? }