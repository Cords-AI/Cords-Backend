<?php

namespace App\Utils;

use GuzzleHttp\Client;

class SearchUtils
{
    public static function getProvince(string $lat, string $lng): ?string
    {
        $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=$lat,$lng&key={$_ENV['GOOGLE_API_KEY']}";
        $client = new Client();
        $request = new \GuzzleHttp\Psr7\Request('GET', $url);
        $result = $client->send($request);
        $contents = json_decode($result->getBody()->getContents());

        return self::findAdministrativeAreaLevelOne($contents->results);
    }

    private static function findAdministrativeAreaLevelOne(array $results): ?string
    {
        foreach ($results as $result) {
            foreach ($result->address_components as $addressComponent) {
                if (in_array('administrative_area_level_1', $addressComponent->types)) {
                    return $addressComponent->short_name;
                }
            }
        }
        return null;
    }
}
