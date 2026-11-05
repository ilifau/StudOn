<?php declare(strict_types=1);

namespace FAU\User\Data;

use FAU\RecordData;

class PaymentInfo extends RecordData
{
    protected const tableName = 'fau_user_paymentinfo';
    protected const hasSequence = false;
    protected const keyTypes = [
        'person_id' => 'integer',
        'term_year' => 'integer',
        'term_type_id' => 'integer',
        'category_id' => 'integer'
    ];
    protected const otherTypes = [
    ];

    protected int $person_id;
    protected int $term_year;
    protected int $term_type_id;
    protected int $category_id;

    public function __construct(
        int $person_id,
        int $term_year,
        int $term_type_id,
        int $category_id
    ) {
        $this->person_id = $person_id;
        $this->term_year = $term_year;
        $this->term_type_id = $term_type_id;
        $this->category_id = $category_id;
    }

    public static function model(): self
    {
        return new self(0,0,0,0);
    }

    /**
     * @return int
     */
    public function getPersonId() : int
    {
        return $this->person_id;
    }

    /**
     * @return int
     */
    public function getTermYear() : int
    {
        return $this->term_year;
    }

    /**
     * @return int
     */
    public function getTermTypeId() : int
    {
        return $this->term_type_id;
    }

    /**
     * @return int
     */
    public function getCategoryId() : int
    {
        return $this->category_id;
    }
}