<?php

namespace App;

class LocalizedString
{
    public ?string $en;
    public ?string $fr;

    public function __construct()
    {
        $this->en = null;
        $this->fr = null;
    }
}
