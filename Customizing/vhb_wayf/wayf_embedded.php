<?php
/**
 * *************************************************************
 *
 *  Copyright notice
 *
 *  @author  Jari Fischer
 *  @copyright 2016 ssystems (it-development@ssystems.de)
 *
 *  All rights reserved
 *
 *  This script is part of the Moodle project. The Moodle project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 **************************************************************
 */
/**
 * Function to get the js configuration for the embedded wayf service
 *
 * @param idpConfig configuration of the auth vhb block
 *
 * @return string
 */
function get_embedded_wayf($idpConfig) {
    global $CFG;

    $handlerURL= $idpConfig['wayf_sp_handlerURL'];
    $returnURL= $idpConfig['wayf_return_url'];
    $entityID=$idpConfig['wayf_sp_entityID'];
    $wayf_base_url = $idpConfig['wayf_base_url'];
    $submitButtonText = $idpConfig['submitButtonText'];
    $textAbove = $idpConfig['textAbove'];

    $embedded_wayf_js = $wayf_base_url . "/js/embedded-wayf.js";
    $maintainance_js = $wayf_base_url . "/js/maintainance.js";
    $dfnwayf_url = 'https://wayf.aai.dfn.de/DFN-AAI/wayf/WAYF';
    $vhb_override_config_url = $wayf_base_url . "/settings/vhbWAYFConfig.js";
    print("<script type='text/javascript' src='" . $wayf_base_url . "/ispData.php'></script>");

    //$maintenancemessage = get_string('maintenance','block_vhbauth');
    //$pixroot = (string)$CFG->wwwroot . "/blocks/vhbauth/pix";
    $embeddedWayf = <<<EOF
    <script type="text/javascript"><!--
//////////////////// ESSENTIAL SETTINGS ////////////////////

// URL of the WAYF to use
// [Mandatory]
var wayf_URL = "$dfnwayf_url";

// EntityID of the Service Provider that protects this Resource
// [Mandatory]
var wayf_sp_entityID = "$entityID";
				
// Shibboleth Service Provider handler URL
// [Mandatory, if wayf_use_discovery_service = false]
var wayf_sp_handlerURL = "$handlerURL";
				
// URL on this resource that the user should be returned to after authentication
// [Mandatory]
var wayf_return_url = "$returnURL";

//////////////////// RECOMMENDED SETTINGS ////////////////////

// Background color as CSS color value, e.g. 'black' or '#000000'
// [Optional, default: #F0F0F0]
var wayf_background_color = 'none';

//////////////////// ADVANCED SETTINGS ////////////////////

// Overwrites the text of the submit button
// [Optional, default: none]
var wayf_overwrite_submit_button_text = "$submitButtonText";

// Overwrites the intro text above the drop-down list
// [Optional, default: none]
var wayf_overwrite_intro_text = "$textAbove";

// Helper function
function getJSON(url) {
	var xmlHttp = new XMLHttpRequest();
	xmlHttp.open( "GET", url , false );
	xmlHttp.send( null );
	return xmlHttp.responseText;
}


</script>
<!-- load local settings -->
<script type="text/javascript" src="blocks/vhbauth/js/settings.js"></script>
<!-- override settings with vhb settings  -->
<script type="text/javascript" src="$vhb_override_config_url"></script>
<!-- create lists  -->
<script type="text/javascript" src="$embedded_wayf_js"></script>
<!-- maintainance message -->
<script type="text/javascript" src="$maintainance_js"></script>

<noscript>

vhb Login: Javascript is not enabled for your web browser. Please enable Javascript to use this service!

</noscript>
EOF;
    return $embeddedWayf;
}
