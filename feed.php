<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* News feed script.
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*/

// fau: shortRssLink - process parameters from the shortened link
if ($_GET['feed_id'] != '')
{
	$_GET['user_id'] = $_GET['feed_id'];	
	unset($_GET['feed_id']);
	
}
if ($_GET['feed_data'] != '')
{
	$_GET['hash'] = $_GET['feed_data'];	
	unset($_GET['feed_data']);
}
// fau.

// fau: httpPath - use defined http path for feeds
$GLOBALS['USE_ILIAS_HTTP_PATH_FROM_INI'] = true;
// fau.

include_once "Services/Context/classes/class.ilContext.php";
ilContext::init(ilContext::CONTEXT_RSS);

require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();

if ($_GET["user_id"] != "")
{
	include_once("./Services/Feeds/classes/class.ilUserFeedWriter.php");
	$writer = new ilUserFeedWriter($_GET["user_id"], $_GET["hash"]);
	$writer->showFeed();
}
else if ($_GET["ref_id"] != "")
{
	include_once("./Services/Feeds/classes/class.ilObjectFeedWriter.php");
	$writer = new ilObjectFeedWriter($_GET["ref_id"], false, $_GET["purpose"]);
	$writer->showFeed();
}
else if ($_GET["blog_id"] != "")
{
	include_once("Modules/Blog/classes/class.ilObjBlog.php");
	ilObjBlog::deliverRSS($_GET["blog_id"]);
}
?>
