<?php

class ilFileObjectToStorageMigrationHelper
{
    protected $base_path = '/var/iliasdata/ilias/default/ilFile';
    public const MIGRATED = ".migrated";
    /**
     * @var ilDBInterface
     */
    protected $database;

    // fau: fixFileMigration - class variable for the runner
    /**
     * @var ilFileObjectToStorageMigrationRunner
     */
    protected  $runner;
    // fau.

    /**
     * @param string        $base_path
     * @param ilDBInterface $database
     */
    public function __construct(string $base_path, ilDBInterface $database)
    {
        $this->base_path = $base_path;
        $this->database = $database;
    }

    // fau: fixFileMigration - setter for the runner
    /**
     * @param ilFileObjectToStorageMigrationRunner $runner
     */
    public function setRunner(ilFileObjectToStorageMigrationRunner $runner)
    {
        $this->runner = $runner;
    }
    // fau.

    public function getNext() : ilFileObjectToStorageDirectory
    {
        $query = "SELECT file_id 
                    FROM file_data 
                    WHERE 
                        (rid IS NULL OR rid = '')
                        AND (file_id != ''  AND file_id IS NOT NULL) 
                    LIMIT 1;";
        $r = $this->database->query($query);
        $d = $this->database->fetchObject($r);
        if (!isset($d->file_id) || null === $d->file_id || '' === $d->file_id) {
            throw new LogicException("error fetching file_id");
        }

        $file_id = (int) $d->file_id;

        // fau: fixFileMigration - create missing source directory
        $file_path = $this->createPathFromId($file_id);

        if (!is_dir($file_path)) {
            mkdir($file_path, 0755, true);

            $this->runner->logMigratedFile(
                $file_id,
                '',
                0,
                $file_path,
                'fixed',
                '',
                'missing source directory created'
            );
        }

        return new ilFileObjectToStorageDirectory($file_id, $file_path);
        // fau.
    }

    private function createPathFromId(int $file_id) : string
    {
        $path = [];
        $found = false;
        $num = $file_id;
        $path_string = '';
        for ($i = 3; $i > 0; $i--) {
            $factor = pow(100, $i);
            if (($tmp = (int) ($num / $factor)) or $found) {
                $path[] = $tmp;
                $num = $num % $factor;
                $found = true;
            }
        }

        if (count($path)) {
            $path_string = (implode('/', $path) . '/');
        }

        return $this->base_path . '/' . $path_string . 'file_' . $file_id;
    }

}
