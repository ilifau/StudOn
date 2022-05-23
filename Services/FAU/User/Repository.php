<?php declare(strict_types=1);

namespace FAU\User;

use FAU\User\Data\Education;

/**
 * Repository for accessing FAU user data
 */
class Repository
{
    /**
     * @var \ilDBInterface
     */
    protected $db;

    public function __construct(\ilDBInterface $a_db)
    {
        $this->db = $a_db;
    }


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
        $query = "SELECT * FROM fau_user_educations WHERE user_id" . $this->db->quote($user_id, 'integer');
        if (isset($type))  {
            $query .= " AND " . $this->db->quoteIdentifier('type') . ' = ' . $this->db->quote($type, 'text');
        }
        $result = $this->db->query($query);

        $educations = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $educations[] = new Education(
                (int) $row['user_id'],
                (string) $row['type'],
                (string) $row['key'],
                (string) $row['value'],
                isset($row['key_title']) ? (string) $row['key_title'] : null,
                isset($row['value_text']) ? (string) $row['value_text'] : null);
        }
        return $educations;
    }


}