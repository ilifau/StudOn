<?php
/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilStrictCliCronManager
 * @author Michael Jansen <mjansen@databay.de>
 */
interface ilCronManagerInterface
{
    // fau: singleCronJob - new interface function runSingleJob()
    /**
     * Run one job
     * @param string $job_id
     * @return mixed
     */
    public function runSingleJob($job_id);
    // fau.

    /**
     * Run all active jobs
     */
    public function runActiveJobs();
}
