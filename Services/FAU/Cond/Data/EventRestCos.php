<?php  declare(strict_types=1);

namespace FAU\Cond\Data;

use FAU\RecordData;


/**
 * Validity of an event restriction for certain courses of study
 * These records are hand-written and not yet synced from campo
 * The relation stores the cos_ids for a combination of event_id and restriction
 * If records exist with isException() == false
 *      then the restriction is only valid for these courses of study
 * If records exist with isException() == true
 *      then the restriction is not valid for these courses of stuy
 */
class EventRestCos extends RecordData
{
    protected const tableName = 'fau_cond_evt_rest_cos';
    protected const hasSequence = false;
    protected const keyTypes = [
        'event_id' => 'integer',
        'restriction' => 'text',
        'cos_id' => 'integer',
    ];
    protected const otherTypes = [
        'exception' => 'integer',
    ];
    private int $event_id;
    private string $restriction;
    private int $cos_id;
    private bool $exception;


    public function __construct(
        int    $event_id,
        string $restriction,
        int    $cos_id,
        bool   $exception
    )
    {
        $this->event_id = $event_id;
        $this->restriction = $restriction;
        $this->cos_id = $cos_id;
        $this->exception = $exception;
    }

    public static function model() : self
    {
        return new self(0, '', 0, false);
    }

    /**
     * @return int
     */
    public function getEventId(): int
    {
        return $this->event_id;
    }

    /**
     * @return string
     */
    public function getRestriction(): string
    {
        return $this->restriction;
    }

    /**
     * @return int
     */
    public function getCosId(): int
    {
        return $this->cos_id;
    }

    /**
     * @return bool
     */
    public function isException(): bool
    {
        return $this->exception;
    }
}