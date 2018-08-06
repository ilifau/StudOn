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
ilContext::init(ilContext::CONTEXT_RSS_AUTH);

require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();

global $lng, $ilSetting;

$feed_set = new ilSetting("news");

// fau: shortRssLink - allow authentication by url parameters
// fau: shortRssLink - init user id to prevent empty feeds
// fau: shortRssLink - add content type headers
// fau: shortRssLink - use shortened feed url instead of ILIAS_HTTP_PATH for Channel
require_once ('./Services/Link/classes/class.ilLink.php');
$feed_url = ilLink::_getShortlinkBase('https://').'privfeed/'.$_GET["user_id"].'/'.$_GET["hash"] .'.rss';
$feed_login = ilObjUser::_lookupLogin((int) $_GET["user_id"]);

$feed_pass = ilObjUser::_getFeedPass((int) $_GET["user_id"]);
$feed_pass_short = substr($feed_pass, 0, strlen($_GET["hash"]));

$feed_user_id_by_name = ilObjUser::_lookupId($_SERVER['PHP_AUTH_USER']);
$feed_pass_by_name = ilObjUser::_getFeedPass($feed_user_id_by_name);

if ($feed_set->get("enable_private_feed")
	and
	(	($_SERVER['PHP_AUTH_USER'] == $feed_login and md5($_SERVER['PHP_AUTH_PW']) == $feed_pass)
		or
		(strlen($_GET["hash"]) >= 8 and $_GET["hash"] == $feed_pass_short)
	))
{
	// neeed because functions in ilNewsItem check this id
	$ilUser->setId((int) $_GET["user_id"]);

	include_once("./Services/Feeds/classes/class.ilUserFeedWriter.php");
	// Third parameter is true for private feed
	$writer = new ilUserFeedWriter($_GET["user_id"], $_GET["hash"], true);
	Header('Content-type: application/rss+xml');
	$writer->showFeed();
}
else if ($_GET["ref_id"] != "" and md5($_SERVER['PHP_AUTH_PW']) == $feed_pass_by_name)
{
	// neeed because functions in ilNewsItem check this id
	$ilUser->setId((int) $feed_user_id_by_name);

	include_once("./Services/Feeds/classes/class.ilObjectFeedWriter.php");
	// Second parameter is optional to pass on to database-level to get news for logged-in users
	$writer = new ilObjectFeedWriter($_GET["ref_id"], $feed_user_id_by_name);
	Header('Content-type: application/rss+xml');
	$writer->showFeed();
}
else
{
	// send appropriate header, if password is wrong, otherwise
	// there is no chance to re-enter it (unless, e.g. the browser is closed)
	if (md5($_SERVER['PHP_AUTH_PW']) != $feed_pass_by_name)
	{
		Header('Content-type: application/rss+xml');
		Header("WWW-Authenticate: Basic realm=\"ILIAS Newsfeed\"");
		Header("HTTP/1.0 401 Unauthorized");
		exit;
	}

	include_once("./Services/Feeds/classes/class.ilFeedItem.php");
	include_once("./Services/Feeds/classes/class.ilFeedWriter.php");

	$blankFeedWriter = new ilFeedWriter();
	$feed_item = new ilFeedItem();
	$lng->loadLanguageModule("news");

	if ($ilSetting->get('short_inst_name') != "")
	{
		$blankFeedWriter->setChannelTitle($ilSetting->get('short_inst_name'));
	}
	else
	{
		$blankFeedWriter->setChannelTitle("ILIAS");
	}

	if (!$feed_set->get("enable_private_feed"))
	{
		$blankFeedWriter->setChannelAbout($feed_url);
		$blankFeedWriter->setChannelLink(ILIAS_HTTP_PATH);
		// title
		$feed_item->setTitle($lng->txt("priv_feed_no_access_title"));

		// description
		$feed_item->setDescription($lng->txt("priv_feed_no_access_body"));
		$feed_item->setLink(ILIAS_HTTP_PATH);
	}
	else
	{
		$blankFeedWriter->setChannelAbout($feed_url);
		$blankFeedWriter->setChannelLink(ILIAS_HTTP_PATH);
		// title
		$feed_item->setTitle($lng->txt("priv_feed_no_auth_title"));

		// description
		$feed_item->setDescription($lng->txt("priv_feed_no_auth_body"));
		$feed_item->setLink(ILIAS_HTTP_PATH);
		$feed_item->setAbout($url);
		// fim.
	}
	$blankFeedWriter->addItem($feed_item);
	$blankFeedWriter->showFeed();
}
// fau.
?>
