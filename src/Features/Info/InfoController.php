<?php

namespace App\Features\Info;

use App\LocalizedString;
use FOS\RestBundle\Controller\Annotations\Get;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;

class InfoController
{
    #[Get('/info')]
    public function getInfo()
    {
        $reader = new Reader(__DIR__ . '/GeoLite2-City_20230221/GeoLite2-City.mmdb');
        $ip = $_SERVER['HTTP_X_REAL_IP'];
        if (!empty($_ENV['DEV_IP'])) {
            $ip = $_ENV['DEV_IP'];
        }

        $vm = new InfoViewModel();

        try {
            $record = $reader->city($ip);
            $raw = $record->raw;
            $vm->locationLevel2 = new LocalizedString();
            $vm->locationLevel2->en = $raw['subdivisions'][0]['iso_code'];
            $vm->locationLevel2->fr = $raw['subdivisions'][0]['iso_code'];
            $vm->locationLevel3 = new LocalizedString();
            $vm->locationLevel3->en = $raw['country']['names']['en'];
            $vm->locationLevel3->fr = $raw['country']['names']['fr'];
            $locationData = $record->location->jsonSerialize();
            $cityData = $record->city->jsonSerialize();
            $vm->locationLevel1 = new LocalizedString();
            if (!empty($cityData['names']['en'])) {
                $vm->locationLevel1->en = $cityData['names']['en'];
            }
            if (!empty($cityData['names']['fr'])) {
                $vm->locationLevel1->fr = $cityData['names']['fr'];
            }
            $vm->lat = $locationData['latitude'];
            $vm->lng = $locationData['longitude'];
        } catch(AddressNotFoundException $e) {
        }

        return new JsonResponse($vm);
    }
}
