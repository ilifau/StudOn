<?php /**
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

namespace ILIAS\UICore;

use ILIAS\Data\URI;
use ILIAS\GlobalScreen\Scope\Layout\Factory\ContentModification;
use ILIAS\GlobalScreen\Scope\Layout\Factory\FooterModification;
use ILIAS\GlobalScreen\Scope\Layout\Provider\AbstractModificationProvider;
use ILIAS\GlobalScreen\Scope\Layout\Provider\ModificationProvider;
use ILIAS\GlobalScreen\ScreenContext\Stack\CalledContexts;
use ILIAS\GlobalScreen\ScreenContext\Stack\ContextCollection;
use ILIAS\UI\Component\Legacy\Legacy;
use ILIAS\UI\Component\MainControls\Footer;
use ILIAS\GlobalScreen\Scope\Layout\Factory\TitleModification;
use ILIAS\GlobalScreen\Scope\Layout\Factory\ShortTitleModification;
use ILIAS\GlobalScreen\Scope\Layout\Factory\ViewTitleModification;

/**
 * Class ilPageContentProvider
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 * @author Michael Jansen <mjansen@databay.de>
 * @author Maximilian Becker <mbecker@databay.de>
 */
class PageContentProvider extends AbstractModificationProvider implements ModificationProvider
{

    /**
     * @var string
     */
    private static $content = "";
    /**
     * @var string
     */
    private static $perma_link = "";
    /**
     * @var string
     */
    private static $title = "";
    /**
     * @var string
     */
    private static $short_title = "";
    /**
     * @var string
     */
    private static $view_title = "";

    /**
     * @param string $content
     */
    public static function setContent(string $content) : void
    {
        self::$content = $content;
    }

    /**
     * @param string $content
     */
    public static function setTitle(string $title) : void
    {
        self::$title = $title;
    }

    /**
     * @param string $content
     */
    public static function setShortTitle(string $short_title) : void
    {
        self::$short_title = $short_title;
    }

    /**
     * @param string $content
     */
    public static function setViewTitle(string $view_title) : void
    {
        self::$view_title = $view_title;
    }


    /**
     * @param string $perma_link
     */
    public static function setPermaLink(string $perma_link) : void
    {
        self::$perma_link = $perma_link;
    }


    /**
     * @inheritDoc
     */
    public function isInterestedInContexts() : ContextCollection
    {
        return $this->context_collection->main();
    }


    /**
     * @inheritDoc
     */
    public function getContentModification(CalledContexts $screen_context_stack) : ?ContentModification
    {
        return $this->globalScreen()->layout()->factory()->content()->withModification(function (?Legacy $content) : ?Legacy {
            if ($content === null) {
                return null;
            }
            $ui = $this->dic->ui();
            return $ui->factory()->legacy(
                $ui->renderer()->render($content) . self::$content
            );
        })->withLowPriority();
    }


    public function getTitleModification(CalledContexts $screen_context_stack) : ?TitleModification
    {
        return $this->globalScreen()->layout()->factory()->title()->withModification(
            function (?string $content) : string {
                return self::$title;
            }
        )->withLowPriority();
    }

    public function getShortTitleModification(CalledContexts $screen_context_stack) : ?ShortTitleModification
    {
        return $this->globalScreen()->layout()->factory()->short_title()->withModification(
            function (?string $content) : string {
                return self::$short_title;
            }
        )->withLowPriority();
    }

    public function getViewTitleModification(CalledContexts $screen_context_stack) : ?ViewTitleModification
    {
        return $this->globalScreen()->layout()->factory()->view_title()->withModification(
            function (?string $content) : string {
                return self::$view_title;
            }
        )->withLowPriority();
    }

    /**
     * @inheritDoc
     */
    public function getFooterModification(CalledContexts $screen_context_stack) : ?FooterModification
    {
        // fau: ownFooter - add the own links
        return $this->globalScreen()->layout()->factory()->footer()->withModification(function (?Footer $footer = null) : ?Footer {
            $f = $this->dic->ui()->factory();
            $lng = $this->dic->language();

            $links = [];
            $texts = [];

            if (\ilCust::get('ilias_footer_type') != 'exam') {
                $links[] = $f->link()->standard($lng->txt('footer_contact'), \ilCust::get("ilias_footer_contact_url"));
                $links[] = $f->link()->standard($lng->txt('footer_imprint'), \ilCust::get("ilias_footer_imprint_url"));
                $links[] = $f->link()->standard($lng->txt('footer_privacy'), \ilCust::get("ilias_footer_privacy_url"));
                $links[] = $f->link()->standard($lng->txt('obj_accs'), \ilCust::get("ilias_footer_accessibility_url"));
            }

            // fau: countUsersOnline - call the counter
            $users_online = \ilSession::_getUsersOnline(600, true) . ' (10min) ⸱ ' . \ilSession::_getUsersOnline(3600). ' (1h)';
            // fau.

            $texts[] = $lng->txt('footer_realised_with') . ' '. ILIAS_VERSION_NUMERIC;
            $texts[] = $lng->txt('footer_server') . ' ' . current(explode('.', gethostbyaddr($_SERVER['SERVER_ADDR'])));
            $texts[] = $lng->txt('footer_active_users') . ' ' . $users_online;

            // fau: devmodeFooter - show memory usage as MB
            if (DEVMODE) {
                // execution time
                $t1 = explode(" ", $GLOBALS['ilGlobalStartTime']);
                $t2 = explode(" ", microtime());
                $diff = $t2[0] - $t1[0] + $t2[1] - $t1[1];

                $texts[] = "Execution Time: " . round($diff, 4) . " seconds";

                if (function_exists("memory_get_usage")) {
                    $texts[] =
                        "Memory Usage: " . round(memory_get_usage() / (1024 * 1024)) . " MB";
                }
                if (function_exists("xdebug_peak_memory_usage")) {
                    $texts[] =
                        "XDebug Peak Memory Usage: " . round(xdebug_peak_memory_usage() / (1024 * 1024)) . " MB";
                }
             }
             // fau.

                // ILIAS Version and Text
//            $ilias_version = $this->dic->settings()->get('ilias_version');
//            $text = "powered by ILIAS (v{$ilias_version})";

            // Imprint
//            if ($_REQUEST["baseClass"] !== "ilImprintGUI" && \ilImprint::isActive()) {
//                $imprint_title = $this->dic->language()->txt("imprint");
//                $imprint_url = \ilLink::_getStaticLink(0, "impr");
//                $links[] = $f->link()->standard($imprint_title, $imprint_url);
//            }

            // system support contacts
//            if (($system_support_url = \ilSystemSupportContactsGUI::getFooterLink()) !== '') {
//                $system_support_title = \ilSystemSupportContactsGUI::getFooterText();
//                $links[] = $f->link()->standard($system_support_title, $system_support_url);
//            }

            // output translation link
            if (\ilObjLanguageAccess::_checkTranslate() && !\ilObjLanguageAccess::_isPageTranslation()) {
                $translation_url = \ilObjLanguageAccess::_getTranslationLink();
                $translation_title = $this->dic->language()->txt('translation');
                $links[] = $f->link()->standard($translation_title, $translation_url)->withOpenInNewViewport(true);
            }

            // accessibility control concept
//            if (($accessibility_control_url = \ilAccessibilityControlConceptGUI::getFooterLink()) !== '') {
//                $accessibility_control_title = \ilAccessibilityControlConceptGUI::getFooterText();
//                $links[] = $f->link()->standard($accessibility_control_title, $accessibility_control_url);
//            }

            // report accessibility issue
//            if (($accessibility_report_url = \ilAccessibilitySupportContactsGUI::getFooterLink()) !== '') {
//                $accessibility_report_title = \ilAccessibilitySupportContactsGUI::getFooterText();
//                $links[] = $f->link()->standard($accessibility_report_title, $accessibility_report_url);
//            }

            $footer = $f->mainControls()->footer($links, implode('<br />', $texts));

            $tosWithdrawalGui = new \ilTermsOfServiceWithdrawalGUIHelper($this->dic->user());
            $footer = $tosWithdrawalGui->modifyFooter($footer);

            if (self::$perma_link !== "") {
                $footer = $footer->withPermanentURL(new URI(self::$perma_link));
            }

            return $footer;
        });
        // fau.
    }
}
