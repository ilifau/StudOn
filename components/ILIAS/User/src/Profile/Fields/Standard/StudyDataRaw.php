<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/
// fau: userData - class for study data raw field in user profile
declare(strict_types=1);

namespace ILIAS\User\Profile\Fields\Standard;

use ILIAS\User\Context;
use ILIAS\User\Profile\Fields\NoOverrides;
use ILIAS\User\Profile\Fields\FieldDefinition;
use ILIAS\User\Profile\Fields\AvailableSections;
use ILIAS\Language\Language;

class StudyDataRaw implements FieldDefinition
{
    use NoOverrides;

    public function getIdentifier(): string
    {
        return 'studydata_raw';
    }

    public function getLabel(Language $lng): string
    {
        return $lng->txt($this->getIdentifier());
    }

    public function getSection(): AvailableSections
    {
        return AvailableSections::Other;
    }

    public function availableInCertificatesForcedTo(): ?bool
    {
        return false;
    }


    public function getLegacyInput(
        Language $lng,
        Context $context,
        ?\ilObjUser $user = null
    ): \ilFormPropertyGUI {
        return $this->buildNonEditableInput($lng, $user);
    }    

    private function buildNonEditableInput(
        Language $lng,
        ?\ilObjUser $user
    ): \ilFormPropertyGUI {
        $input = new \ilTextAreaInputGUI('');
        $input->setInfo($lng->txt('fau_read_only'));
        $input->setValue(
            $this->retrieveValueFromUser($user)
        );
        return $input;
    }    

    public function addValueToUserObject(
        \ilObjUser $user,
        mixed $input,
        ?\ilPropertyFormGUI $form = null
    ): \ilObjUser {
        return $user;
    }

    public function retrieveValueFromUser(?\ilObjUser $user): string
    {
        global $DIC;
        if(!$user) {
            return "";
        }   
        $person =  $DIC->fau()->user()->repo()->getPersonOfUser($user->getId());
        if ($person) {
            return json_encode(json_decode($person->getStudydata()), JSON_PRETTY_PRINT);
        }
        else return "";
    }
}
// fau.
