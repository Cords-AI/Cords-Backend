<?php

namespace App\Search;

class Result
{
    public function __construct(
        public string $id,
        public ?bool $exactTitle = null,
        public ?bool $titleStartsWith = null,
        public ?bool $titleContains = null,
        public ?float $vectorDistance = null,
        public ?int $distance = null,
    ) {
    }
}
