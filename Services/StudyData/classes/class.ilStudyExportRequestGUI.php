<?php
/* fau: extendedAccess - new class ilStudyExportRequestGUI. */

/**
* Class lStudyExportRequestGUI
*
* @ilCtrl_Calls ilStudyExportRequestGUI:
*
*/
class ilStudyExportRequestGUI
{
    /** @var ilGlobalTemplate  */
    public $tpl;

    public function __construct()
    {
        global $DIC;

        $DIC->language()->loadLanguageModule('registration');

        $this->tpl = $DIC->ui()->mainTemplate();
        $this->tpl->setTitle("Antrag auf Teilnehmerdaten-Export");
    }


    public function executeCommand()
    {
        global $DIC;

        $cmd = $DIC->ctrl()->getCmd("showRequestForm");
        $this->$cmd();

        return true;
    }

    public function cancel()
    {
        ilUtil::redirect("index.php");
    }
    
    
    public function showRequestForm()
    {
        global $DIC;

        $ilUser = $DIC->user();
        $ilCtrl = $DIC->ctrl();
        $rbacsystem = $DIC->rbac()->system();

        include_once('Services/PrivacySecurity/classes/class.ilPrivacySettings.php');
        $privacy = ilPrivacySettings::_getInstance();

        $tpl = new ilTemplate("tpl.export_request.html", true, true, "Services/StudyData");

        if ($ilUser->getId() == ANONYMOUS_USER_ID) {
            $link = "login.php?target=" . $_GET["target"] . "&cmd=force_login&lang=" . $ilUser->getCurrentLanguage();
            ilUtil::redirect($link);
        } elseif ($rbacsystem->checkAccess('export_member_data', $privacy->getPrivacySettingsRefId())) {
            $tpl->touchBlock('hasright_message');
        } else {
            $tpl->setCurrentBlock('export_request');
            $tpl->setVariable("FORMACTION", $ilCtrl->getFormAction($this));
            $tpl->setVariable("LOGIN", $ilUser->getLogin());
            ilDatePresentation::setUseRelativeDates(false);
            $tpl->setVariable("DATE", ilDatePresentation::formatDate(new ilDate(time(), IL_CAL_UNIX)));
            $tpl->setVariable("USERNAME", $ilUser->getFullname());
            $tpl->parseCurrentBlock();
        }

        $this->tpl->setContent($tpl->get());
        $this->tpl->printToStdout();
    }


    public function submitRequest()
    {
        global $ilUser;

        $tpl = new ilTemplate("tpl.export_request.html", true, true, "Services/StudyData");
        $tpl->setCurrentBlock('export_mail');
        $tpl->setVariable("LOGIN", $ilUser->getLogin());
        ilDatePresentation::setUseRelativeDates(false);
        $tpl->setVariable("DATE", ilDatePresentation::formatDate(new ilDate(time(), IL_CAL_UNIX)));
        $tpl->setVariable("USERNAME", $ilUser->getFullname());
        $tpl->parseCurrentBlock();
        $message = $tpl->get();

        require_once('Services/Mail/classes/class.ilMail.php');
        $mail = new ilMail($ilUser->getId());

        $mail->sendMail(
            'studon@uni-erlangen.de',
            $ilUser->getEmail(),
            '',
            'Antrag auf Teilnehmerdaten-Export',
            $message,
            [],
            false
        );
            
        $tpl = new ilTemplate("tpl.export_request.html", true, true, "Services/StudyData");
        $tpl->touchBlock('sent_message');
        $this->tpl->setContent($tpl->get());
        $this->tpl->printToStdout();
    }
}
