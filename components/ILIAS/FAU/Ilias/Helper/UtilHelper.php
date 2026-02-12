<?php

namespace FAU\Ilias\Helper;

/**
 * trait for providing additional ilUtil methods
 */
trait UtilHelper 
{
    // private in ILIAS 7 / public in ILIAS 8
    public static function unmaskTag(string $a_str, string $tag, array $fix_param = []): string
    {
        $a_str = str_replace("&lt;" . $tag . "&gt;", "<" . $tag . ">", $a_str);
        $a_str = str_replace("&lt;/" . $tag . "&gt;", "</" . $tag . ">", $a_str);

        foreach ($fix_param as $p) {
            $k = $p["param"];
            $v = $p["value"];
            $a_str = str_replace(
                "&lt;$tag $k=\"$v\"&gt;",
                "<" . "$tag $k=\"$v\"" . ">",
                $a_str
            );
        }
        return $a_str;
    }
}