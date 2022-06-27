<?php  declare(strict_types=1);

namespace FAU\Cond\Data;

use FAU\RecordData;

class Restriction extends RecordData
{
    protected const tableName = 'fau_cond_restrictions';
    protected const hasSequence = false;
    protected const keyTypes = [
        'id' => 'integer',
    ];
    protected const otherTypes = [
        'restriction' => 'text',
        'type' => 'text',
        'compare' => 'text',
        'number' => 'integer',
        'compulsory' => 'text'
    ];

    protected int $id;
    protected string $restriction;
    protected ?string $type;
    protected ?string $compare;
    protected ?int $number;
    protected ?string $compulsory;

    public function __construct(
        int $id,
        string $restriction,
        ?string $type,
        ?string $compare,
        ?int $number,
        ?string $compulsory
    )
    {
        $this->id = $id;
        $this->restriction = $restriction;
        $this->type = $type;
        $this->compare = $compare;
        $this->number = $number;
        $this->compulsory = $compulsory;
    }

    public static function model(): self
    {
        return new self(0,'', null,null,null, null);
    }

    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getRestriction() : string
    {
        return $this->restriction;
    }

    /**
     * @return string|null
     */
    public function getType() : ?string
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getCompare() : ?string
    {
        return $this->compare;
    }

    /**
     * @return int|null
     */
    public function getNumber() : ?int
    {
        return $this->number;
    }

    /**
     * @return string|null
     */
    public function getCompulsory() : ?string
    {
        return $this->compulsory;
    }
}