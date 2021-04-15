<?php
require_once ('./Customizing/vhb_wayf/loginConfig.php');
require_once('./Customizing/vhb_wayf/wayf_embedded.php');
?>
<!DOCTYPE html>
<html lang="de" dir="ltr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>StudOn - VHB Login</title>

    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" type="text/css" href="./templates/default/delos.css" />
    <link rel="stylesheet" type="text/css" href="./Customizing/vhb_wayf/style/style.css">

</head>
<body class="std" >
<div id="ilAll">
    <div id="ilTopBar" class="ilTopBar ilTopFixed">
    </div>
    <div class="ilMainHeader ilTopFixed">
        <header class="container-fluid ilContainerWidth">
            <div class="row">
                <a class="navbar-brand" href="index.php">
                    <img src="templates/default/images/studon/studon.svg" alt="Logo" class="studonLogo noMirror" />
                </a>
            </div>
        </header>
    </div>
    <div id="mainspacekeeper" class="container-fluid ilContainerWidth ilFixedTopSpacer">
        <div class="row" style="position: relative;">
            <div id="fixed_content" class=" ilContentFixed">
                <div id="mainscrolldiv" class="ilStartupFrame container">
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
    <div id="minheight"></div>
    <footer id="ilFooter" class="ilFooter">
        <div class="container-fluid ilContainerWidth">
            <div class="ilFooterContainer">
                <img class="ilFooterLogo" src="./templates/default/images/studon/fau-white.svg" alt="FAU Logo" />
                <p>Finanziert aus Studienzuschüssen</p>
            </div>

            <div class="ilFooterContainer">
                <img class="ilFooterLogo" src="./templates/default/images/studon/ili-white.svg" alt="ILI Logo" />
                <p><a href="mailto:studon@fau.de">Kontakt</a> / <a target="_blank" href="http://www.fim.uni-erlangen.de/index.php/de/impressum-fuss">Impressum</a></p>
            </div>
        </div>
    </footer>
</div>
</body>
</html>