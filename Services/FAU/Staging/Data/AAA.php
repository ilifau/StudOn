<?php  declare(strict_types=1);

namespace FAU\Staging\Data;

use FAU\RecordData;

class AAA extends RecordData
{
    protected const tableName = '';
    protected const hasSequence = false;
    protected const keyTypes = [
        '' => '',
        '' => '',
        '' => '',
        '' => '',
    ];
    protected const otherTypes = [
        '' => '',
        '' => '',
        '' => '',
        '' => '',
    ];

    public function __construct(

    )
    {
    }

    public static function model(): self
    {
        return new self();
    }
}