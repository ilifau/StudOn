<?php  declare(strict_types=1);

namespace FAU\Staging\Data;

use FAU\RecordData;

class Exam extends RecordData
{
    protected const tableName = 'campo_exam';
    protected const hasSequence = false;
    protected const keyTypes = [
        'porgnr' => 'integer'
    ];
    protected const otherTypes = [
        'pnr' => 'text',
        'psem' => 'integer',
        'ptermin' => 'integer',
        'pdatum' => 'date',
        'titel' => 'text',
        'veranstaltung' => 'text',
    ];

    protected int $porgnr;
    protected string $pnr;
    protected int $psem;
    protected ?int $ptermin;
    protected ?string $pdatum;
    protected string $titel;
    protected ?string $veranstaltung;

    public function __construct(
        int $porgnr,
        string $pnr,
        int $psem,
        ?int $ptermin,
        ?string $pdatum,
        string $titel,
        ?string $veranstaltung
    )
    {
        $this->porgnr = $porgnr;
        $this->pnr = $pnr;
        $this->psem = $psem;
        $this->ptermin = $ptermin;
        $this->pdatum = $pdatum;
        $this->titel = $titel;
        $this->veranstaltung = $veranstaltung;
    }

    public static function model(): self
    {
        return new self(0,'',0,null,null,'',null);
    }

    /**
     * @return int
     */
    public function getPorgnr() : int
    {
        return $this->porgnr;
    }

    /**
     * @return string
     */
    public function getPnr() : string
    {
        return $this->pnr;
    }

    /**
     * @return int
     */
    public function getPsem() : int
    {
        return $this->psem;
    }

    /**
     * @return int|null
     */
    public function getPtermin() : ?int
    {
        return $this->ptermin;
    }

    /**
     * @return string|null
     */
    public function getPdatum() : ?string
    {
        return $this->pdatum;
    }

    /**
     * @return string
     */
    public function getTitel() : string
    {
        return $this->titel;
    }

    /**
     * @return string|null
     */
    public function getVeranstaltung() : ?string
    {
        return $this->veranstaltung;
    }
}