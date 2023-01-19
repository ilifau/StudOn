<?php

namespace FAU\Ilias\Data;

use FAU\Study\Data\ImportId;

/**
 * Container Data of courses or groups
 */
class ContainerData
{
    const TYPE_GROUP = 'grp';
    const TYPE_COURSE = 'crs';

    private string $title;
    private ?string $description;
    private string $type;
    private int $ref_id;
    private int $obj_id;
    private ImportId $import_id;


    public function __construct (
        string $title,
        ?string $description,
        string $type,
        int $ref_id,
        int $obj_id,
        ImportId $import_id
    ) {
        $this->title = $title;
        $this->description = $description;
        $this->type = $type;
        $this->ref_id = $ref_id;
        $this->obj_id = $obj_id;
        $this->import_id = $import_id;
    }

    /**
     * Get the object type ('crs' or 'grp')
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * Get the reference id
     */
    public function getRefId() : int
    {
        return $this->ref_id;
    }

    /**
     * Get the object id
     */
    public function getObjId() : int
    {
        return $this->obj_id;
    }

    /**
     * Get the object title
     */
    public function getTitle() : string
    {
        return $this->title;
    }

    /**
     * Get the object description
     */
    public function getDescription() : ?string
    {
        return $this->description;
    }

    /**
     * @return ImportId
     */
    public function getImportId(): string
    {
        return $this->import_id;
    }
}
