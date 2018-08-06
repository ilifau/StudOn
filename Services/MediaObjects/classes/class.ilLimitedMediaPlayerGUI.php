<?php
/**
 * Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE
 */

require_once("./Services/MediaObjects/classes/class.ilLimitedMediaPlayerUsage.php");

/**
 * fim: [media] GUI class for limited media player.
 *
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @version $Id$
 */
class ilLimitedMediaPlayerGUI
{	
	/**
	 * @var parameters stored with the media object, added to the request
	 */
	private $parent_id;
	private $page_id;
	private $mob_id;
	private $file;
	private $height;
	private $width;
	private $limit_context;
	private $limit_count;	
	
	/**
	 * @var	viewing mode of the page, added to the request
	 *		'edit', 'preview' or 'presentation'
	 *		'presentation' indicates the use in a test run
	 */
	private $mode;
	
	/**
	 * @var internal status variables 
	 */
	private $usage = null;
	private $current_uses = 0;
	private $testpass = 0;	


	/**
	 * Constructor
	 * Initializes internal variables and objects
	 * Does not change anything
	 */
	function __construct()
	{
		global $tpl, $lng, $ilUser;

		$this->parent_id = $_GET["parent_id"];
		$this->page_id = $_GET["page_id"];
		$mob_id = explode("_", $_GET["mob_id"]);
		$this->mob_id = $mob_id[count($mob_id)-1];
		$this->file = '../../'.substr($_GET["source"], 2);
		$this->height = $_GET["height"];
		$this->width = $_GET["width"];
		
		$this->limit_context = $_GET["limit_context"];
		$this->limit_count = $_GET["limit_count"];
		$this->mode = $_GET['mode'];

		
		if ($this->limit_context == ilLimitedMediaPlayerUsage::CONTEXT_TESTPASS)
		{
			if ($this->mode == 'presentation')
			{
				// presentation mode indicates a test run
				// here the test pass should be relevant
				require_once "./Modules/Test/classes/class.ilObjTest.php";
				$this->test_id = ilObjTest::_getTestIDFromObjectID($this->parent_id);
				$this->active_id = ilObjTest::_getActiveIdOfUser($ilUser->getId(),$this->test_id);
				$this->testpass = ilObjTest::_getPass($this->active_id);
			}
			else
			{
				// media is not shown in a test run

				// switch the counting context to login session
				// this will not change the pass related count
				$this->limit_context = ilLimitedMediaPlayerUsage::CONTEXT_SESSION;

				// set the limit count to unlimited
				$this->limit_count = 0;
			}
		}

		// get the stored usage 
		$this->usage = new ilLimitedMediaPlayerUsage($this->page_id, $this->mob_id, $ilUser->getId(), $this->limit_context);
		$this->current_uses = $this->usage->getUses($this->testpass);
	}

	
	/**
	 * Handle the player request
	 * The player is called from an iframe of the media object
	 * ilCtrl is not used 
	 */
	public function executeCommand()
	{
		if ($_GET['cmd'] == 'update')
		{
			// store a new counting of uses
			// this is called by ajax an does not need to return something 
			$this->usage->updateUsage($_GET['set_uses'], $this->testpass);
		}
		elseif ($this->current_uses < $this->limit_count or $this->limit_count == 0)
		{
			// show a page with the embedded player
			$this->showPlayer();
		}
		else
		{
			// show a message that the maximum uses are reached
			$this->showLimitReachedScreen();
		}
	}
	
	/**
	 * Show a page with embedded player
	 * The page is called from an iframe, so it only shows the player and the counters
	 */
	protected function showPlayer()
	{
		global $lng;

        require_once "./Services/MediaObjects/classes/class.ilPlayerUtil.php";
        require_once "./Services/jQuery/classes/class.iljQueryUtil.php";

		$update_url = "limited_player.php?cmd=update"
			."&limit_context=".$this->limit_context
			."&mode=".$this->mode
			."&parent_id=".$this->parent_id
			."&page_id=".$this->page_id
			."&mob_id=".$this->mob_id
			."&set_uses=".($this->current_uses + 1);
		
		$tpl = new ilTemplate("tpl.limited_media_player.html", true, true, "Services/MediaObjects");
        $tpl->setVariable("JQUERY_URL", "../../". iljQueryUtil::getLocaljQueryPath());
        $tpl->setVariable("PLAYER_JS_URL", "../../". ilPlayerUtil::getLocalMediaElementJsPath());
        $tpl->setVariable("PLAYER_CSS_URL", "../../". ilPlayerUtil::getLocalMediaElementCssPath());

		$tpl->setVariable("FILE", $this->file);
		$tpl->setVariable("UPDATE_URL", $update_url);
		$tpl->setVariable("SET_USES", $this->current_uses + 1);
		$tpl->setVariable("CURRENT_USES", (int) $this->current_uses);
		$tpl->setVariable("CURRENT_USES_TEXT", $lng->txt("cont_limit_starts_current_plays_text"));
		$tpl->setVariable("MAX_USES", $this->limit_count == 0 ? $lng->txt('no_limit') : (int) $this->limit_count);
		$tpl->setVariable("MAX_USES_TEXT", $lng->txt("cont_limit_starts_max_plays_text"));
		echo $tpl->get();
		
		//echo "<pre>"; var_dump($this); echo "</pre>";
	}


	/**
	 * Show a message that the limit of uses is reached
	 * The page is called from an iframe, so it only shoes the message
	 */
	protected function showLimitReachedScreen()
	{
		global $lng;
		
		$tpl = new ilTemplate("tpl.limited_media_player_limit_reached.html", true, true, "Services/MediaObjects");
		$tpl->setVariable("MESSAGE", $lng->txt("cont_limit_starts_limit_reached_text"));
		$tpl->show();
		
		//echo "<pre>"; var_dump($this); echo "</pre>";
	}
}
?>