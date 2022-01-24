<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* News feed script.
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*/

// fau: httpPath - use defined http path for feeds
$GLOBALS['USE_ILIAS_HTTP_PATH_FROM_INI'] = true;
// fau.

include_once "Services/Context/classes/class.ilContext.php";
ilContext::init(ilContext::CONTEXT_RSS_AUTH);

require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();

global $lng, $ilSetting;

$feed_set = new ilSetting("news");

// fau: shortRssLink - allow authentication by feed passwird hash in url
// fau: shortRssLink - init user id to prevent empty feeds
// fau: shortRssLink - treat object links like user links
// fau: shortRssLink - add content type headers

if ($_SERVER['PHP_AUTH_USER']) { 	// base authentication

    $user_id = ilObjUser::_lookupId($_SERVER['PHP_AUTH_USER']);
    $pw_hash = ilObjUser::_getFeedPass($user_id);
    $pw_hash_short = substr($pw_hash, 0, 8);

    $authentified = (md5($_SERVER['PHP_AUTH_PW']) == $pw_hash);
} elseif ((int) $_GET['user_id']) { 	// authentication by password hash

    $user_id = $_GET['user_id'];
    $pw_hash = ilObjUser::_getFeedPass($user_id);
    $pw_hash_short = substr($pw_hash, 0, 8);

    $authentified = (substr($_GET['hash'], 0, 8) == $pw_hash_short);
}

if ($feed_set->get("enable_private_feed") && $authentified) {
    // needed because functions in ilNewsItem check this id
    $ilUser->setId($user_id);

    if ((int) $_GET['ref_id']) {
        // specific feed for repository object
        $writer = new ilObjectFeedWriter($_GET["ref_id"], $user_id);
    } else {
        // third parameter is true for private feed
        // hash must be shortened password hash for private user feeds (ChannelAbout link)
        $writer = new ilUserFeedWriter($user_id, $pw_hash_short, true);
    }

    Header('Content-type: application/rss+xml');
    $writer->showFeed();
} else {

    // send appropriate header, if password is wrong, otherwise
    // there is no chance to re-enter it (unless, e.g. the browser is closed)
    if (!$authentified && !(int) $_GET['user_id']) {
        Header('Content-type: application/rss+xml');
        Header("WWW-Authenticate: Basic realm=\"ILIAS Newsfeed\"");
        Header("HTTP/1.0 401 Unauthorized");
        exit;
    }
    // fau.
    include_once("./Services/Feeds/classes/class.ilFeedItem.php");
    include_once("./Services/Feeds/classes/class.ilFeedWriter.php");

    $blankFeedWriter = new ilFeedWriter();
    $feed_item = new ilFeedItem();
    $lng->loadLanguageModule("news");

    if ($ilSetting->get('short_inst_name') != "") {
        $blankFeedWriter->setChannelTitle($ilSetting->get('short_inst_name'));
    } else {
        $blankFeedWriter->setChannelTitle("ILIAS");
    }
    // fau: shortRssLink - use shortened feed url instead of ILIAS_HTTP_PATH for ChannelAbout
    $ref = ($_GET['ref_id'] ? '/' . $_GET['ref_id'] : '');
    $feed_url = ilLink::_getShortlinkBase('https://') . 'privfeed/' . $user_id . '/' . $pw_hash_short . $ref . '.rss';

    if (!$feed_set->get("enable_private_feed")) {
        $blankFeedWriter->setChannelAbout($feed_url);
        $blankFeedWriter->setChannelLink(ILIAS_HTTP_PATH);
        // title
        $feed_item->setTitle($lng->txt("priv_feed_no_access_title"));

        // description
        $feed_item->setDescription($lng->txt("priv_feed_no_access_body"));
        $feed_item->setLink(ILIAS_HTTP_PATH);
    } else {
        $blankFeedWriter->setChannelAbout($feed_url);
        $blankFeedWriter->setChannelLink(ILIAS_HTTP_PATH);
        // title
        $feed_item->setTitle($lng->txt("priv_feed_no_auth_title"));

        // description
        $feed_item->setDescription($lng->txt("priv_feed_no_auth_body"));
        $feed_item->setLink(ILIAS_HTTP_PATH);
        $feed_item->setAbout($feed_url);
    }
    $blankFeedWriter->addItem($feed_item);
    $blankFeedWriter->showFeed();
    // fau.
}
