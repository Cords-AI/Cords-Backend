<?php

namespace App\Search;

use Doctrine\Persistence\ManagerRegistry;
use Exception;

class RelatedSearchService implements SearchServiceInterface, \JsonSerializable
{
    use SearchTrait;

    public function __construct()
    {
        if ($_ENV['APP_ENV'] == 'prod' && empty($_ENV['CHROMA_SERVICE_URL'])) {
            throw new Exception('CHROMA_SERVICE_URL not set');
        }

        $this->pageSize = 5;
        $this->maxDistance = 10000;
    }

    public function execute(ManagerRegistry $doctrine)
    {
        set_time_limit(60);

        $this->filterLocal = $this->currentResourceDelivery === 'local' || $this->currentResourceDelivery === 'regional';
        $this->filterProvincial = $this->currentResourceDelivery === 'provincial';

        $this->doctrine = $doctrine;
        $body = new RemoteSearchParams();
        $body->query = $this->q;

        $conn = $doctrine->getConnection()->getNativeConnection();

        $sql = "SET SESSION group_concat_max_len=18446744073709551615";
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        if ($this->filterLocal) {
            $sql = "DROP TEMPORARY TABLE IF EXISTS distances; CREATE TEMPORARY TABLE distances AS SELECT
                        resource_id as id,
                        ST_Distance_Sphere(
                                point(:lng, :lat),
                                point(lng, lat)
                            ) as distance
                    FROM address WHERE
                        lat and lng is not null
                    HAVING distance < :maxDistance
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(":lat", $this->lat);
            $stmt->bindValue(":lng", $this->lng);
            $stmt->bindValue(":maxDistance", $this->maxDistance);
            $stmt->execute();
        }

        $sql = "DROP TEMPORARY TABLE IF EXISTS potential_resources; CREATE TEMPORARY TABLE potential_resources AS
                    SELECT id FROM resource
                    WHERE
                      computed_canonical_record_id IS NULL
                      AND is_deleted = 0
        ";

        $provinceSql = "";
        if ($this->filterProvincial) {
            $provinceSql = " AND (JSON_OVERLAPS((SELECT JSON_ARRAYAGG(delivery_province.full_name)
                      FROM delivery_province
                      WHERE delivery_province.resource_id = :currentResourceId),
                     (SELECT JSON_ARRAYAGG(delivery_province.full_name)
                      FROM delivery_province
                      WHERE delivery_province.resource_id = resource.id))
                      OR delivery != 'provincial') ";

            if (!empty($this->provinceFromSearch)) {
                $provinceSql = " AND (
                                EXISTS (SELECT 1
                                    FROM delivery_province
                                    WHERE delivery_province.resource_id = resource.id
                                    GROUP BY delivery_province.resource_id
                                    HAVING COUNT(*) = 1
                                    AND MAX(delivery_province.abbreviated_name) = :searchProvince)
                                OR delivery != 'provincial') ";
            }
        }

        $localRegionalSql = "";
        if ($this->filterLocal) {
            $localRegionalSql = " AND (id IN (SELECT id FROM distances) OR (delivery != 'local' AND delivery != 'regional'))";
        }

        $sql = "$sql $provinceSql $localRegionalSql AND delivery = :currentDelivery AND id != :currentResourceId GROUP BY cords.resource.id";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(":currentResourceId", $this->currentResourceId);

        if ($this->currentResourceDelivery === 'provincial') {
            if (!empty($this->provinceFromSearch)) {
                $stmt->bindValue(":searchProvince", $this->provinceFromSearch);
            }
        }

        if (!empty($this->currentResourceDelivery)) {
            $stmt->bindValue(":currentDelivery", $this->currentResourceDelivery);
        }

        $stmt->execute();
        $stmt->closeCursor();

        $this->executeCommonPortion($body, $doctrine, $conn);
    }


}
