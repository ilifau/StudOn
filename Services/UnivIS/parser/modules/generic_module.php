<?php
/** UnivIS update script
 * Fetches content from UnivIS via PRG interface in XML and stores 
 * it into a mysql database
 */

class GenericModule
{
    var $module;

    function __construct($module)
    {
		$this->module = $module;
    }

    function getUrl($uConf)
    {
		return $uConf['prg_url']
			. 'search='.$this->module
			. '&show=xml'
			. ($uConf['name'] ? '&name='.urlencode(utf8_decode($uConf['name'])) : '')
			. ($uConf['department'] ? '&department='.urlencode($uConf['department']) : '');
    }

    function hasMoreUrls()
    {
		return false;
    }
}
?>
