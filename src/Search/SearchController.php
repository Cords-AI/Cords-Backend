<?php

namespace App\Search;

use App\CachedJsonResponse;
use App\Utils\SearchUtils;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations\Get;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends AbstractController
{
    #[Get('/search')]
    public function get(Request $request, ManagerRegistry $doctrine)
    {
        ini_set('memory_limit', '4096M');

        $showDistances = true;

        $q = $request->get('q');

        $lat = $request->get('lat');
        $lng = $request->get('lng');

        $delivery = $request->query->all()['filter']['delivery'] ?? [];

        $province = null;
        if(!empty($lat) && !empty($lng)) {
            $province = SearchUtils::getProvince($lat, $lng);
        }

        $ids = $request->query->all()['ids'] ?? [];

        if (empty($q) && count($ids)) {
            return new CachedJsonResponse(new Clipboard($doctrine, $ids));
        }

        $searchService = new SearchService();

        if($q) {
            $searchStringCity = $this->calculateCityFromSearchString($q, $doctrine, $lng, $lat);
            if($searchStringCity && $searchStringCity->population > 10_000) {
                $searchStringProvince = $searchStringCity->province_id;
                $distance = $this->calculateDistance($lat, $lng, $searchStringCity->lat, $searchStringCity->lng);
                if($distance > 100) {
                    $lat = $searchStringCity->lat;
                    $lng = $searchStringCity->lng;
                    $province = $searchStringCity->province_id;
                    $showDistances = false;
                }
            } else {
                $searchStringProvince = $this->calculateProvinceFromSearchString($q);
                if($searchStringProvince && $searchStringProvince != $province) {
                    $lat = null;
                    $lng = null;
                    $showDistances = false;
                    $province = $searchStringProvince;
                }
            }
        }

        $searchService
            ->q($q)
            ->lat($lat)
            ->lng($lng)
            ->delivery($delivery)
            ->province($province)
            ->showDistances($showDistances)
            ->offset($request->get('page'))
            ->pageSize($request->get('pageSize'))
        ;

        $filters = $request->get('filter');
        if ($filters) {
            $filters = array_filter($filters, fn ($a) => $a === "true");
            $partners = array_keys($filters);
            $searchService->partners($partners);
        }

        if($request->get('distance')) {
            $distance = $request->get('distance') * 1000;
        } else {
            /* @deprecated: distance in meters  */
            $distance = $request->get('maxDistance');
        }

        if ($distance) {
            $searchService->maxDistance($distance);
        }

        $searchService->execute($doctrine);

        return new CachedJsonResponse($searchService);
    }

    private function calculateProvinceFromSearchString(string $query): string|null
    {
        $query = urldecode($query);
        $queryWithoutAccents = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $query);
        $matches = [];

        $provinces = implode('|', Provinces::getProvinceList());
        $provinceRegex = "/$provinces| BC | PEI |Newfoundland & Labrador|Newfoundland/i";

        preg_match($provinceRegex, " $queryWithoutAccents ", $matches);

        if (!count($matches)) {
            return null;
        }
        return Provinces::getAbbreviationFromName(Provinces::formatProvinceName(current($matches)));
    }

    private function calculateCityFromSearchString(string $query, ManagerRegistry $doctrine, string $lng, string $lat): \stdClass|null
    {
        $query = urldecode($query);
        $queryWithoutAccents = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $query);

        $sql = "SELECT * FROM canadacities WHERE :query LIKE CONCAT('%', city_ascii, '%') 
                ORDER BY ST_Distance_Sphere(point(canadacities.lng, canadacities.lat), point(:lng, :lat)) LIMIT 1";

        $connection = $doctrine->getConnection()->getNativeConnection();
        $stmt = $connection->prepare($sql);
        $stmt->bindParam(':query', $queryWithoutAccents);
        $stmt->bindParam(':lng', $lng);
        $stmt->bindParam(':lat', $lat);
        $stmt->execute();

        $result = $stmt->fetchObject();

        if (!$result) {
            return null;
        }
        return $result;
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        // Convert degrees to radians
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $deltaLat = $lat2 - $lat1;
        $deltaLon = $lon2 - $lon1;

        $a = sin($deltaLat/2) * sin($deltaLat/2) + cos($lat1) * cos($lat2) * sin($deltaLon/2) * sin($deltaLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        $distance = $earthRadius * $c;

        return $distance;
    }
}
