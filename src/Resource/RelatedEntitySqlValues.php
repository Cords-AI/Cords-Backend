<?php

namespace App\Resource;

class RelatedEntitySqlValues
{
    public array $insertValues;
    public array $deleteValues;

    public function __construct()
    {
        $this->insertValues = [];
        $this->deleteValues = [];
    }

    public function getInsertValues(): string
    {
        return implode(', ', $this->insertValues);
    }

    public function getDeleteValues(): string
    {
        return implode(', ', $this->deleteValues);
    }

}
