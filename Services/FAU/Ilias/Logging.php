<?php

namespace FAU\Ilias;

use ILIAS\DI\Container;
use FAU\Ilias\Data\RegLog;

/**
 * Logging of changes in course/group members or waiting list
 */
class Logging
{
    protected Container $dic;
    protected \ilLanguage $lng;
    protected Repository $repository;
    
    protected $user_cache = [];
    protected $object_cache = [];
    
    

    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
        $this->lng = $dic->language();
        $this->repository = $this->dic->fau()->ilias()->repo();
    }

    /**
     * Add an entry to the registration log
     */
    public function addRegLog(
        string $action, 
        int $user_id, 
        int $obj_id, 
        ?int $timestamp = null, 
        int $to_confirm = 0, 
        ?int $module_id = null, 
        ?string $subject = null
    ) {
        
        $entry = new RegLog(
            0,
            $timestamp ?? time(),
            $action,
            $this->dic->user()->getId(),
            $user_id,
            $obj_id,
            $to_confirm,
            $module_id,
            $subject
        );
        
        $this->repository->save($entry);
    }

    /**
     * Add an "updateWaitingList" log entry based on the data in the waiting list
     */
    public function addWaitingListUpdate(int $user_id, int $obj_id) 
    {
        $this->repository->save($this->repository->getRegLogEntryFromWaitingList($this->dic->user()->getId(), $user_id, $obj_id));    
    }

    /**
     * Get the registration log of a course or group as a CSV string
     */
    public function getRegLogAsCsv(int $obj_id) : string
    {
        $relative = \ilDatePresentation::useRelativeDates();
        \ilDatePresentation::setUseRelativeDates(false);
        
        $rows = [];
        
        // header row
        $rows[] = [
          'id',
          'time',
          'action',
          'actor',
          'user',
          'object',
          'waiting',
          'module',
          'subject'
        ];
        
        // data rows
        foreach ($this->repository->getRegLogsByObjId($obj_id) as $entry)   {
            $rows[] = [
              $entry->getId(),
              $this->getTimestampEntry($entry->getTimestamp()),
              $entry->getAction(),
              $this->getUserEntry($entry->getActorId()),
              $this->getUserEntry($entry->getUserId()),
              $this->getObjectEntry($entry->getObjId()),
              $this->getStatusEntry($entry->getAction(), $entry->getToConfirm()),
              $this->getModuleEntry($entry->getModuleId()),
              $entry->getSubject()
            ];
        }
        
        $writer = new \ilCSVWriter();
        $writer->setDoUTF8Decoding(true);
        $writer->setDelimiter('"');
        $writer->setSeparator(';');
        
        foreach ($rows as $row) {
            foreach ($row as $col) {
                $writer->addColumn($col);
            }
            $writer->addRow();
        }

        \ilDatePresentation::setUseRelativeDates($relative);
        
        return $writer->getCSVString();
    }
    
    
    protected function getUserEntry(int $user_id) : string
    {
        if (!isset($this->user_cache[$user_id])) {
            $user = new \ilObjUser($user_id);
            
            $this->user_cache[$user_id] = $user->getFullname(50) . ' (' . $user->getLogin() . ')';
        }
        return $this->user_cache[$user_id];
    }
    
    protected function getObjectEntry(int $obj_id) : string
    {
        if (!isset($this->object_cache[$obj_id])) {
            $this->object_cache[$obj_id] = \ilStr::shortenText(\ilObject::_lookupTitle($obj_id), 0, 50);
        }
        return $this->object_cache[$obj_id];

    }
    
    protected function getStatusEntry(string $action, int $to_confirm) : string
    {
        if (in_array($action, ['addToWaitingList', 'updateWaitingList'])) {
            switch ($to_confirm)
            {
                case \ilWaitingList::REQUEST_NOT_TO_CONFIRM:
                    return $this->lng->txt('sub_status_normal');
                case \ilWaitingList::REQUEST_TO_CONFIRM:
                    return $this->lng->txt('sub_status_request');
                case \ilWaitingList::REQUEST_CONFIRMED:
                    return $this->lng->txt('sub_status_confirmed');
            }
        }
        return '-';
    }
    
    
    protected function getModuleEntry(?int $module_id) : string
    {
        foreach($this->dic->fau()->study()->repo()->getModules([(int) $module_id]) as $module) {
            return $module->getLabel();
        }
        return '';
    }
    
    
    protected function getTimestampEntry(int $timestamp) 
    {
        return \ilDatePresentation::formatDate(new \ilDateTime($timestamp, IL_CAL_UNIX), false, false, true);
    }
}