<?php

/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Audio/Video Player Utility
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$
 * @ingroup
 */
class ilPlayerUtil
{
    /**
     * Get local path of jQuery file
     */
    public static function getLocalMediaElementJsPath()
    {
        return "./node_modules/mediaelement/build/mediaelement-and-player.min.js";
    }

    /**
     * Get local path of jQuery file
     */
    public static function getLocalMediaElementCssPath()
    {
        return "./node_modules/mediaelement/build/mediaelementplayer.min.css";
    }

    /**
     * Init mediaelement.js scripts
     */
    public static function initMediaElementJs($a_tpl = null)
    {
        global $DIC;

        $tpl = $DIC["tpl"];
        
        if ($a_tpl == null) {
            $a_tpl = $tpl;
        }
        
        foreach (self::getJsFilePaths() as $js_path) {
            $a_tpl->addJavaScript($js_path);
        }
        foreach (self::getCssFilePaths() as $css_path) {
            $a_tpl->addCss($css_path);
        }
    }
    
    /**
     * Get css file paths
     *
     * @param
     * @return
     */
    public static function getCssFilePaths()
    {
        // fau: jumpMedia - included plugin for jump forward and back links
        return array(self::getLocalMediaElementCssPath(),
            "./Customizing/libs/mediaelement_plugins/dist/skip-back/skip-back.min.css",
            "./Customizing/libs/mediaelement_plugins/dist/jump-forward/jump-forward.min.css"
        );
        // fau.
    }
    
    /**
     * Get js file paths
     *
     * @param
     * @return
     */
    public static function getJsFilePaths()
    {
        // fau: jumpMedia - included plugin for jump forward and back links
        return array(self::getLocalMediaElementJsPath(),
            "./Customizing/libs/mediaelement_plugins/dist/skip-back/skip-back.min.js",
            "./Customizing/libs/mediaelement_plugins/dist/jump-forward/jump-forward.js"
        );
        // fau.
    }
    

    /**
     * Get flash video player directory
     *
     * @return
     */
    public static function getFlashVideoPlayerDirectory()
    {
        return "node_modules/mediaelement/build";
    }
    
    
    /**
     * Get flash video player file name
     *
     * @return
     */
    public static function getFlashVideoPlayerFilename($a_fullpath = false)
    {
        $file = "flashmediaelement.swf";
        if ($a_fullpath) {
            return self::getFlashVideoPlayerDirectory() . "/" . $file;
        }
        return $file;
    }
    
    /**
     * Copy css files to target dir
     *
     * @param
     * @return
     */
    public static function copyPlayerFilesToTargetDirectory($a_target_dir)
    {
        ilUtil::rCopy(
            "./node_modules/mediaelement/build",
            $a_target_dir
        );
    }
}
