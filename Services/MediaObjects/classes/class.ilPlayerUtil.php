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
	private static $mejs_ver = "2_14_2";

    // fim: [media] adapt media player version
    public static function getVersion()
    {
        global $ilCust;
        if ($ilCust->getSetting('media_player_version'))
        {
            return $ilCust->getSetting('media_player_version');
        }
        else
        {
            return self::$mejs_ver;
        }
    }
    // fim.

	/**
	 * Get local path of jQuery file
	 */
	function getLocalMediaElementJsPath()
	{
        // fim: [media] use adapted player version
		return "./Services/MediaObjects/media_element_".self::getVersion()."/mediaelement-and-player.js";
        // fim.
 	}

	/**
	 * Get local path of jQuery file
	 */
	function getLocalMediaElementCssPath()
	{
        // fim: [media] use adapted player version
		return "./Services/MediaObjects/media_element_".self::getVersion()."/mediaelementplayer.min.css";
        // fim.
 	}

 	/**
	 * Init mediaelement.js scripts
	 */
	static function initMediaElementJs($a_tpl = null)
	{
		// fim: [exam] prevent the embedding of media_element
		global $ilCust;
		if ($ilCust->getSetting('tst_prevent_media_player')) 
		{
			return;
		}
		// fim.
		
		global $tpl;
		
		if ($a_tpl == null)
		{
			$a_tpl = $tpl;
		}
		
		foreach (self::getJsFilePaths() as $js_path)
		{
			$a_tpl->addJavaScript($js_path);
		}
		foreach (self::getCssFilePaths() as $css_path)
		{
			$a_tpl->addCss($css_path);
		}
	}
	
	/**
	 * Get css file paths
	 *
	 * @param
	 * @return
	 */
	static function getCssFilePaths()
	{
		return array(self::getLocalMediaElementCssPath());
	}
	
	/**
	 * Get js file paths
	 *
	 * @param
	 * @return
	 */
	static function getJsFilePaths()
	{
		return array(self::getLocalMediaElementJsPath());
	}
	

	/**
	 * Get flash video player directory
	 *
	 * @return
	 */
	static function getFlashVideoPlayerDirectory()
	{
        // fim: [media] use adapted player version
		return "Services/MediaObjects/media_element_".self::getVersion();
        // fim.
	}
	
	
	/**
	 * Get flash video player file name
	 *
	 * @return
	 */
	static function getFlashVideoPlayerFilename($a_fullpath = false)
	{
		$file = "flashmediaelement.swf";
		if ($a_fullpath)
		{
			return self::getFlashVideoPlayerDirectory()."/".$file;
		}
		return $file;
	}
	
	/**
	 * Copy css files to target dir
	 *
	 * @param
	 * @return
	 */
	function copyPlayerFilesToTargetDirectory($a_target_dir)
	{
        // fim: [media] use adapted player version
		ilUtil::rCopy("./Services/MediaObjects/media_element_".self::getVersion(),
			$a_target_dir);
        // fim.
	}
	
}

?>
