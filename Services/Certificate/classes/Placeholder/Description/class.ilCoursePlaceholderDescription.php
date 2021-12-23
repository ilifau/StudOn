<?php
/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author  Niels Theen <ntheen@databay.de>
 */
class ilCoursePlaceholderDescription implements ilCertificatePlaceholderDescription
{
    /**
     * @var ilDefaultPlaceholderDescription
     */
    private $defaultPlaceHolderDescriptionObject;

    /**
     * @var ilLanguage|null
     */
    private $language;

    /**
     * @var array
     */
    private $placeholder;

    /**
     * @param ilDefaultPlaceholderDescription|null $defaultPlaceholderDescriptionObject
     * @param ilLanguage|null $language
     * @param ilUserDefinedFieldsPlaceholderDescription|null $userDefinedFieldPlaceHolderDescriptionObject
     */
    public function __construct(
        ilDefaultPlaceholderDescription $defaultPlaceholderDescriptionObject = null,
        ilLanguage $language = null,
        ilUserDefinedFieldsPlaceholderDescription $userDefinedFieldPlaceHolderDescriptionObject = null
    ) {
        global $DIC;

        if (null === $language) {
            $language = $DIC->language();
        }
        $this->language = $language;

        if (null === $defaultPlaceholderDescriptionObject) {
            $defaultPlaceholderDescriptionObject = new ilDefaultPlaceholderDescription($language, $userDefinedFieldPlaceHolderDescriptionObject);
        }
        $this->defaultPlaceHolderDescriptionObject = $defaultPlaceholderDescriptionObject;

        $this->placeholder = $this->defaultPlaceHolderDescriptionObject->getPlaceholderDescriptions();
        $this->placeholder['COURSE_TITLE'] = $this->language->txt('crs_title');
        $this->placeholder['DATE_COMPLETED'] = ilUtil::prepareFormOutput($language->txt('certificate_ph_date_completed'));
        $this->placeholder['DATETIME_COMPLETED'] = ilUtil::prepareFormOutput($language->txt('certificate_ph_datetime_completed'));
    }

    // fau: courseCertData - new function addMoreCourseData
    /**
     * @param $objId
     */
    public function addMoreCourseData($objId)
    {
        $olp = ilObjectLP::getInstance($objId);
        if ($olp->isActive()) {
            $this->language->loadLanguageModule('trac');
            $this->placeholder["COURSE_USER_MARK"] = $this->language->txt('trac_mark');
            $this->placeholder["COURSE_USER_COMMENT"] = $this->language->txt('trac_comment');
        }

        foreach (ilCourseDefinedFieldDefinition::_getFields($objId) as $field) {
            $name = str_replace('[', '(', $field->getName());
            $name = str_replace(']', ')', $name);

            $this->placeholder[$name] = $name;
        }
    }
    // fau.

    /**
     * This methods MUST return an array containing an array with
     * the the description as array value.
     *
     * @param null $template
     * @return mixed - [PLACEHOLDER] => 'description'
     */
    public function createPlaceholderHtmlDescription(ilTemplate $template = null) : string
    {
        if (null === $template) {
            $template = new ilTemplate('tpl.default_description.html', true, true, 'Services/Certificate');
        }

        $template->setVariable("PLACEHOLDER_INTRODUCTION", $this->language->txt('certificate_ph_introduction'));

        $template->setCurrentBlock("items");
        foreach ($this->placeholder as $id => $caption) {
            $template->setVariable("ID", $id);
            $template->setVariable("TXT", $caption);
            $template->parseCurrentBlock();
        }

        return $template->get();
    }

    /**
     * This method MUST return an array containing an array with
     * the the description as array value.
     *
     * @return mixed - [PLACEHOLDER] => 'description'
     */
    public function getPlaceholderDescriptions() : array
    {
        return $this->placeholder;
    }
}
