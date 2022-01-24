<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

define('IL_INTERNAL_LINK_SCRIPT', 'goto.php');


/**
* Class for creating internal links on e.g repostory items.
* This class uses goto.php to create permanent links
*
* @author Stefan Meyer <meyer@leifos.com>
* @version $Id$
*
*/
class ilLink
{
    public static function _getLink($a_ref_id, $a_type = '', $a_params = array(), $append = "")
    {
        global $DIC;

        $ilObjDataCache = $DIC["ilObjDataCache"];

        if (!strlen($a_type)) {
            $a_type = $ilObjDataCache->lookupType($ilObjDataCache->lookupObjId($a_ref_id));
        }
        $param_string = '';
        if (is_array($a_params) && count($a_params)) {
            foreach ($a_params as $name => $value) {
                $param_string .= ('&' . $name . '=' . $value);
            }
        }
        switch ($a_type) {
            case 'git':
            //case 'pg':
                return ILIAS_HTTP_PATH . '/' . IL_INTERNAL_LINK_SCRIPT . '?client_id=' . CLIENT_ID . $param_string . $append;
            
            default:
                return ILIAS_HTTP_PATH . '/' . IL_INTERNAL_LINK_SCRIPT . '?target=' . $a_type . '_' . $a_ref_id . $append . '&client_id=' . CLIENT_ID . $param_string;
        }
    }

    /**
     * Get static link
     *
     * @access public
     * @static
     *
     * @param int reference id
     * @param string object type
     * @param bool fallback to goto.php if robots are disabled
     * @return string goto.html or goto.php link
     */
    public static function _getStaticLink(
        $a_ref_id,
        $a_type = '',
        $a_fallback_goto = true,
        $append = ""
    ) {
        global $DIC;

        $ilObjDataCache = $DIC["ilObjDataCache"];

        if (!strlen($a_type)) {
            $a_type = $ilObjDataCache->lookupType($ilObjDataCache->lookupObjId($a_ref_id));
        }
        
        include_once('Services/PrivacySecurity/classes/class.ilRobotSettings.php');
        $robot_settings = ilRobotSettings::_getInstance();
        if (!$robot_settings->robotSupportEnabled()) {
            if ($a_fallback_goto) {
                return ilLink::_getLink($a_ref_id, $a_type, array(), $append);
            } else {
                return false;
            }
        }

        // fau: linkPermaShort - generate shortened perma link for studon

        // the server path is shorter (/ for studon, /dev/ for studon-dev etc. )
        // goto is omitted - shortlins are recognized by '.html'
        // client id is omitted, the default client should be used
        // underscored are omitted, type and id is separated by character and number
        return self::_getShortlinkBase() . $a_type . $a_ref_id . urlencode($append) . '.html';

        // urlencode for append is needed e.g. to process "/" in wiki page names correctly
        // return ILIAS_HTTP_PATH.'/goto_'.urlencode(CLIENT_ID).'_'.$a_type.'_'.$a_ref_id.urlencode($append).'.html';

// fau.
    }


    // fau: linkPermaShort - new function to get the base url for sortened perma links
    /**
     * Get the base for shortened permanent links
     * @param	string		$protocol 	full prefix to force a protocol (http:// or https://)
     * 									the default is the protocol of ILIAS_HTTP_PATH
     * @return	string					Url with server path and trailing slash (/ or /dev/ ...)
     */
    public static function _getShortlinkBase($protocol = '')
    {
        $parsed = parse_url(ILIAS_HTTP_PATH);

        // determine host and protocol
        $protocol = empty($protocol) ? $parsed['scheme'] . '://' : $protocol;
        $host = strtolower($parsed['host']);

        // determine shortlink path (/ for studon, /dev/ for studon-dev)
        $path = $parsed['path'];
        $path = str_replace('/studon-', '', $path);
        $path = str_replace('/studon', '', $path);
        $path = empty($path) ? '/' : '/' . $path . '/';

        return $protocol . $host . $path;
    }
    // fau.


    // fau: linkInSameWindow - new function to check whether link targets to the same platform
    /**
     * Check whether a link is on the same host
     * Called in page.xsl to check if link should open in same window
     *
     * @param	string		$link	url
     * @return	boolean				url is in the same platform
     */
    public static function _isLocalLink($link = '')
    {
        $link_host = strtolower(parse_url($link, PHP_URL_HOST));
        if (empty($link_host)) {
            return true;
        }

        $link_host = str_replace('uni-erlangen', 'fau', $link_host);
        $link_host = str_replace('www.', '', $link_host);

        $ilias_host = strtolower($_SERVER['HTTP_HOST']);
        $ilias_host = str_replace('uni-erlangen', 'fau', $ilias_host);
        $ilias_host = str_replace('www.', '', $ilias_host);

        return $link_host == $ilias_host;
    }
    // fau.
}
