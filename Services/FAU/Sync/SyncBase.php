<?php

namespace FAU\Sync;

use ILIAS\DI\Container;

abstract class SyncBase
{
    protected Container $dic;
    protected \FAU\Cond\Service $cond;
    protected \FAU\Org\Service $org;
    protected \FAU\Staging\Service $staging;
    protected \FAU\Study\Service $study;
    protected Service $sync;
    protected \FAU\User\Service $user;
    protected \FAU\Tools\Service $tools;


    private int $items_added = 0;
    private int $items_updated = 0;
    private int $items_deleted = 0;

    private array $errors = [];
    private array $warnings = [];

    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
        $this->org = $dic->fau()->org();
        $this->cond = $dic->fau()->cond();
        $this->staging = $dic->fau()->staging();
        $this->study = $dic->fau()->study();
        $this->sync = $dic->fau()->sync();
        $this->user = $dic->fau()->user();
        $this->tools = $dic->fau()->tools();
    }

    /**
     * Add an error message
     */
    public function addError(string $error) : void
    {
        $this->errors[] = $error;
    }

    /**
     * Check if the call produced an error
     */
    public function hasErrors() : bool
    {
        return !empty($this->errors);
    }

    /**
     * Get a list of error messages
     */
    public function getErrors() : array
    {
        return $this->errors;
    }

    /**
     * Add a warning
     */
    public function addWarning(string $warning) : void
    {
        $this->warnings[] = $warning;
    }

    /**
     * Check if the call produced warnings
     */
    public function hasWarnings() : bool
    {
        return !empty($this->warnings);
    }


    /**
     * Get a list of warnings
     */
    public function getWarnings() : array
    {
        return $this->warnings;
    }


    /**
     * Synchronize data (called by cron job)
     */
    abstract public function synchronize() : void;

    /**
     * Get the number of added items
     */
    public function getItemsAdded() : int
    {
        return $this->items_added;
    }

    /**
     * Increase the number of added items
     */
    protected function increaseItemsAdded(int $number = 1)
    {
        $this->items_added += $number;
    }

    /**
     * Get the number of updated items
     */
    public function getItemsUpdated() : int
    {
        return $this->items_updated;
    }

    /**
     * Increase the number of updated items
     */
    protected function increaseItemsUpdated(int $number = 1)
    {
        $this->items_updated += $number;
    }

    /**
     * Get the number of deleted items
     */
    public function getItemsDeleted() : int
    {
        return $this->items_deleted;
    }

    /**
     * Increase the number of deleted items
     */
    protected function increaseItemsDeleted(int $number = 1)
    {
        $this->items_deleted += $number;
    }


    /**
     * Add an info text to the console and to the log
     */
    protected function info(?string $text)
    {
        if (!\ilContext::usesHTTP()) {
            echo $text . "\n";
        }
        $this->dic->logger()->fau()->info($text);
    }
}

