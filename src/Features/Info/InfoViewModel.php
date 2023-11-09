<?php

namespace App\Features\Info;

use App\LocalizedString;

class InfoViewModel
{
    public ?LocalizedString $locationLevel1;

    public ?LocalizedString $locationLevel2;

    public ?LocalizedString $locationLevel3;

    public ?float $lat;

    public ?float $lng;
}
