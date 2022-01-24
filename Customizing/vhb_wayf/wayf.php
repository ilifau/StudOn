<?php

require_once ('loginConfig.php');
require_once('wayf_embedded.php');

?>
<html>
<head>
<link rel="stylesheet" href="style/style.css">
</head>
<body>
<div id="wayf-wrapper">
            <?php
            // Embedded Wayf wird geladen, falls so gesetzt in der loginConfig
            if (isset($CONFIG['wayf_config'])){
                $config = $CONFIG['wayf_config'];
                $wayfString = get_embedded_wayf($config);
           echo $wayfString;
          }
        ?>
</div>

<script>
    window.onload = function changeWayfClasses() {
        document.getElementById('user_idp_iddtext').classList.add('custom-select');
        document.getElementById('user_idp_iddtext').classList.add('custom-select-lg');
        document.getElementById('user_idp_iddtext').classList.remove('idd_textbox');

        document.getElementById('wayf_logo').src="style/img/vhb_logo.jpg";
    };
</script>
