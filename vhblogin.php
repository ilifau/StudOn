<?php
require_once ('./Customizing/vhb_wayf/loginConfig.php');
require_once('./Customizing/vhb_wayf/wayf_embedded.php');
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <title>StudOn - vhb Login</title>

    <link rel="stylesheet" type="text/css" href="./Customizing/global/skin/StudOn/StudOn.css">
    <link rel="stylesheet" type="text/css" href="./templates/default/delos_cont.css">
    <link rel="stylesheet" type="text/css" href="./Customizing/vhb_wayf/style/style.css">
</head>

<body>

<div class="il-layout-page">
    <header>
        <div class="header-inner">
            <div class="il-logo">
                <a href="#"><img src="./templates/default/images/HeaderIcon.svg" class="img-standard" alt="StudOn"></a>
            </div>

            <!--
            <ul class="il-maincontrols-metabar" role="menubar" aria-label="Metabar">
                <li role="none">
                    <button class="btn btn-bulky" role="menuitem">
                        <span class="glyph" aria-label="Switch Language" role="img">
                            <span class="glyphicon glyphicon-lang" aria-hidden="true"></span>
                        </span>
                        <span class="bulky-label">Language</span>
                    </button>
                </li>
            </ul>
            -->
        </div>
    </header>

    <div class="breadcrumbs"></div>
    <div class="il-system-infos"></div>
    <div class="nav il-maincontrols">
        <div class="il-maincontrols-mainbar">
            <div class="il-mainbar" aria-label="Mainbar"></div>
        </div>
    </div>

    <main class="il-layout-page-content" tabindex="-1">
        <div>
            <div id="mainspacekeeper" class="container-fluid ">
                <div class="row" style="position: relative;">
                    <div id="fixed_content" class=" ilContentFixed">
                        <div id="mainscrolldiv">
                            <div class="media il_HeaderInner">
                                <h1 class="media-heading ilHeader">
                                    Login über die Virtuelle Hoschschule Bayern
                                </h1>
                                <div class="media-body"></div>
                            </div>
                            <div class="ilClearFloat"></div>
                            <div class="ilTabsContentOuter">
                                <div class="clearfix"></div>
                                <span class="ilAccHidden"><a id="after_sub_tabs" name="after_sub_tabs"></a></span>
                                <div class="container-fluid" id="ilContentContainer">
                                    <div class="row">
                                        <div id="il_center_col" class="col-sm-12">
                                            <div class="ilStartupSection">

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

                                                        document.getElementById('wayf_logo').src="./Customizing/vhb_wayf/style/img/vhb_logo.jpg";
                                                    };
                                                </script>

                                                <p>
                                                    Über diese Seite können Sie sich als Teilnehmer/in eines vhb-Kurses bei StudOn einloggen. Bitte beachten Sie, dass der Standardweg zum Zugriff auf
                                                    Ihre vhb-Kurse das vhb-Portal <a href="https://www.vhb.org">www.vhb.org</a> ist.
                                                </p>
                                                <p>
                                                    Verwenden Sie diese Seite, wenn Sie auf einen Kurs aus dem Vorsemester zugreifen möchten,
                                                    der auf StudOn vom Anbieter noch offen gelassen wurde, z.B. zur Prüfungsvorbereitung.
                                                    Dazu müssen Sie lediglich bei der vhb rückgemeldet sein:
                                                </p>
                                                <ol>
                                                    <li>Loggen Sie sich auf <a href="https://www.vhb.org">www.vhb.org</a> im Classic vhb-Kursprogramm ein.</li>
                                                    <li>Drücken Sei den Authentifizieren-Button.</li>
                                                    <li>Melden Sie sich bei Ihrer Heimathochschule mit Ihren Benutzerdaten an.</li>
                                                </ol>
                                                <p>Nach der erfolgreichen Rückmeldung kommen Sie hier, ebenfalls per Anmeldung bei Ihrer Heimathochschule, wieder in StudOn.
                                                    Ihre noch verfügbaren vhb-Kurse finden Sie auf dem Schreibtisch verlinkt.</p>


                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer role="contentinfo">
            <div class="il-maincontrols-footer">

                <div class="il-footer-content">
                    <div class="il-footer-links">
                        <ul>
                            <li><a href="https://www.studon.fau.de/contact">Contact</a></li>
                            <li><a href="https://www.studon.fau.de/imprint">Imprint</a></li>
                            <li><a href="https://www.studon.fau.de/privacy">Privacy</a></li>
                            <li><a href="https://www.studon.fau.de/accessibility">Accessibility</a></li>
                        </ul>
                    </div>
                </div>

                <div class="il-footer-content">
                    <div class="il-footer-text">
                        <img src="./templates/static/images/fau-white.svg" height="45" alt="FAU Logo">
                    </div>
                    <div class="il-footer-text">
                        <img src="./templates/static/images/ili-white.svg" height="45" alt="ILI Logo">
                    </div>
                </div>
            </div>
        </footer>
    </main>
</div>

</body>
</html>