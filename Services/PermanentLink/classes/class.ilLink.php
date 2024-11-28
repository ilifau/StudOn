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

use ILIAS\StaticURL\Services;
use ILIAS\Data\ReferenceId;

/**
 * @deprecated Use ILIAS\Services\StaticURL instead
 */
class ilLink
{
    /**
     * @deprecated
     */
    public static function _getLink(
        ?int $a_ref_id,
        string $a_type = '',
        array $a_params = [],
        string $append = ""
    ): string {
        global $DIC;
        /** @var Services $static_url */
        $static_url = $DIC["static_url"];

        $ilObjDataCache = $DIC["ilObjDataCache"];

        if ($a_type === '' && $a_ref_id) {
            $a_type = $ilObjDataCache->lookupType($ilObjDataCache->lookupObjId($a_ref_id));
        }

        $target = urlencode(CLIENT_ID) . '_' . $a_type . '_' . $a_ref_id . urlencode($append);

        $a_params = array_merge($a_params, [$append]);
        $a_params = array_filter($a_params, static function ($value): bool {
            return $value !== "";
        });

        if (!empty($a_type)) {
            return (string) $static_url->builder()->build(
                $a_type,
                $a_ref_id !== null ? new ReferenceId($a_ref_id) : null,
                $a_params
            );
        }
        return '';
    }

    /**
     * @deprecated
     */
    public static function _getStaticLink(
        ?int $a_ref_id,
        string $a_type = '',
        bool $a_fallback_goto = true,
        string $append = ""
    ): string {
        return self::_getLink($a_ref_id, $a_type, [], $append);
    }

    // fau: linkInSameWindow - new function to check whether link targets to the same platform
    /**
     * Check whether a link is on the same host
     * Called in page.xsl to check if link should open in same window
     *
     * @param	string		$link	url
     * @return	boolean				url is in the same platform
     */
    public static function _isLocalLink($link = '')
    {
        $link_host = strtolower(parse_url($link, PHP_URL_HOST));
        if (empty($link_host)) {
            return true;
        }

        $link_host = str_replace('uni-erlangen', 'fau', $link_host);
        $link_host = str_replace('www.', '', $link_host);

        $ilias_host = strtolower($_SERVER['HTTP_HOST']);
        $ilias_host = str_replace('uni-erlangen', 'fau', $ilias_host);
        $ilias_host = str_replace('www.', '', $ilias_host);

        return $link_host == $ilias_host;
    }
    // fau.    
}
