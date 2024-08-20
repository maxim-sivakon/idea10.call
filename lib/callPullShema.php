<?php


class callPullShema{
    public static function OnGetDependentModule()
    {
        return Array(
            'MODULE_ID' => "istline.call",
            'USE' => Array("PUBLIC_SECTION")
        );
    }
}