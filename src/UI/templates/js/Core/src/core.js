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
 ********************************************************************
 */
<<<<<<< HEAD
=======
<<<<<<<< HEAD:Modules/DataCollection/classes/Ports/Access/class.ilDataCollectionAccessPort.php
/**
 * @author martin@fluxlabs.ch
 */
interface ilDataCollectionAccessPort
{
    public function hasVisibleOrReadPermission(int $refId): bool;

    public function hasReadPermission(int $refId): bool;

    public function hasWritePermission(int $refId): bool;

    public function hasEditPermissionPermission(int $refId): bool;

    public function hasVisiblePermission(int $refId): bool;
}
========
>>>>>>> v9.1

import il from 'il';
import $ from 'jquery';
import replaceContent from './core.replaceContent';
<<<<<<< HEAD
import URLBuilder from './core.URLBuilder';
import URLBuilderToken from './core.URLBuilderToken';

il = il || {};
=======
import Tooltip from './core.Tooltip';
import URLBuilder from './core.URLBuilder';
import URLBuilderToken from './core.URLBuilderToken';

>>>>>>> v9.1
il.UI = il.UI || {};
il.UI.core = il.UI.core || {};

il.UI.core.replaceContent = replaceContent($);
<<<<<<< HEAD
il.UI.core.URLBuilder = URLBuilder;
il.UI.core.URLBuilderToken = URLBuilderToken;
=======
il.UI.core.Tooltip = Tooltip;
il.UI.core.URLBuilder = URLBuilder;
il.UI.core.URLBuilderToken = URLBuilderToken;
>>>>>>>> v9.1:src/UI/templates/js/Core/src/core.js
>>>>>>> v9.1
