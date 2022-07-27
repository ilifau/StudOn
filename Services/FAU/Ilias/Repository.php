<?php

namespace FAU\ILIAS;

/**
 * Repository for accessing ilias data
 */
class Repository
{
    protected \ilDBInterface $db;
    protected \ilLogger $logger;

    /**
     * Constructor
     */
    public function __construct(\ilDBInterface $a_db, \ilLogger $logger)
    {
        $this->db = $a_db;
        $this->logger = $logger;
    }



}