<?php

namespace App\Search;

class LatLng
{
    public ?string $lat;
    public ?string $lng;

    public function __construct(string $lat, string $lng)
    {
        $this->lat = $lat;
        $this->lng = $lng;
    }
}
