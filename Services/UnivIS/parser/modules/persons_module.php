<?php
/** UnivIS update script
 * Fetches content from UnivIS via PRG interface in XML and stores 
 * it into a mysql database
 */

require_once './Services/UnivIS/parser/modules/generic_module.php';

class PersonsModule extends GenericModule
{
    var $module;

    function __construct($module)
    {
		parent::__construct($module);
    }

    function getUrl($uConf)
    {
		return $uConf['prg_url']
			. 'search='.$this->module
			. '&show=xml'
			. ($uConf['name'] ? '&name='.urlencode(utf8_decode($uConf['name'])) : '')
			. ($uConf['fullname'] ? '&fullname='.urlencode(utf8_decode($uConf['fullname'])) : '')
			. ($uConf['department'] ? '&department='.urlencode($uConf['department']) : '');
    }

    function hasMoreUrls()
    {
		return false;
    }
}
?>
