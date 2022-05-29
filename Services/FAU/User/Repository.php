<?php declare(strict_types=1);

namespace FAU\User;

use FAU\User\Data\Education;
use FAU\RecordRepo;


/**
 * Repository for accessing FAU user data
 */
class Repository extends RecordRepo
{
    /**
     * Delete the educations of a user account (e.g. if user is deleted)
     */
    public function deleteEducationsOfUser(int $user_id) : void
    {
        $this->db->manipulateF("DELETE FROM fau_user_educations WHERE user_id = %s", ['int'], [$user_id]);
    }

    /**
     * Get the educations assigned to a user
     * @return Education[]
     */
    public function getEducationsOfUser(int $user_id, ?string $type = null) : array
    {
        $query = "SELECT * FROM fau_user_educations WHERE user_id = " . $this->db->quote($user_id, 'integer');
        if (isset($type))  {
            $query .= " AND " . $this->db->quoteIdentifier('type') . ' = ' . $this->db->quote($type, 'text');
        }
        return $this->queryRecords($query, new Education());
    }

    public function saveEducation(Education $record)
    {
        $this->replaceRecord($record);
    }
    public function deleteEducation(Education $record)
    {
        $this->deleteRecord($record);
    }

}