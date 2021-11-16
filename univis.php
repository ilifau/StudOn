<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * fau: univisLinks - Handle a request from UnivIS.
 *
 * @author	Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @version $Id: $
 */

// fau: httpPath - use defined http path for univis
$GLOBALS['USE_ILIAS_HTTP_PATH_FROM_INI'] = true;
// fau.

include_once "Services/Context/classes/class.ilContext.php";
ilContext::init(ilContext::CONTEXT_RSS);

require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();


/**
 * Handle the request by univis request class
 */
require_once("Services/UnivIS/classes/class.ilUnivisRequest.php");
$request = new ilUnivisRequest();
$request->execute();
