<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * News feed script.
 *
 * @author Alex Killing <alex.killing@gmx.de>
* @author Alex Killing <alex.killing@gmx.de>
 */

// fau: shortRssLink - process parameters from the shortened link
if ($_GET['user_id'] == '0') {
    $_GET['user_id'] = '';
}
if ($_GET['ref_id'] == '0') {
    $_GET['ref_id'] = '';
}
// fau.

// fau: httpPath - use defined http path for feeds
$GLOBALS['USE_ILIAS_HTTP_PATH_FROM_INI'] = true;
// fau.

include_once "Services/Context/classes/class.ilContext.php";
ilContext::init(ilContext::CONTEXT_RSS);

require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();

if ($_GET["user_id"] != "") {
    $writer = new ilUserFeedWriter($_GET["user_id"], $_GET["hash"]);
    $writer->showFeed();
} elseif ($_GET["ref_id"] != "") {
    $writer = new ilObjectFeedWriter($_GET["ref_id"], false, $_GET["purpose"]);
    $writer->showFeed();
} elseif ($_GET["blog_id"] != "") {
    ilObjBlog::deliverRSS($_GET["blog_id"]);
}
