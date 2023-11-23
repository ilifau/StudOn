<?php

use FAU\BaseGUI;
use FAU\Study\Data\ImportId;
use FAU\Tools\Service;
use FAU\Study\Data\Event;

/**
 * GUI for the display of textual information in a modal
 * @ilCtrl_Calls fauTextViewGUI:
 */
class fauTextViewGUI extends BaseGUI
{
    protected Service $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = $this->dic->fau()->tools();
    }


    /**
     * Get an instance of the class
     * @return static
     */
    public static function getInstance() : self
    {
        static $instance = null;
        if (!isset($instance)) {
            $instance = new self();
        }
        return $instance;
    }

    /**
     * Execute a command
     */
    public function executeCommand()
    {
        $this->tpl->loadStandardTemplate();

        $cmd = $this->ctrl->getCmd('show');
        $next_class = $this->ctrl->getNextClass();

        switch ($next_class) {
            default:
                switch ($cmd)
                {
                    default:
                        $this->tpl->setContent('unknown command: ' . $cmd);
                }
        }

        $this->tpl->printToStdout();
    }


    /**
     * Show a text either directly or as a link to a modal
     * @param string $text          Text to be shown (may be HTML)
     * @param string $title         Title for the modal
     * @param int$limit             Number of characters to be shown directly (0: always modal)
     * @param string|null $label    Label for the modal link, if not the shortened text is used
     * @return string
     */
    public function showWithModal(string $text, string $title, int $limit = 0, ?string $label = null) : string
    {
        $clean = strip_tags($text);
        if (strlen($clean) <= $limit) {
            return $text;
        }
        if (isset($label)) {
            $label = '» ' . $label;
        }
        elseif ($limit > 0) {
            $label = '» ' . ilUtil::shortenText($clean, $limit, true);
        }
        else {
            $label = '» ' . $this->lng->txt('show');
        }

        $modal = $this->factory->modal()->roundtrip($title,$this->factory->legacy($text))->withCancelButtonLabel('close');
        $button = $this->factory->button()->shy($label, '#')->withOnClick($modal->getShowSignal());

        return $this->renderer->render([$modal, $button]);
    }
}