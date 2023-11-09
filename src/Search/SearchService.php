<?php

namespace App\Search;

use App\Database;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

class SearchService implements SearchServiceInterface, \JsonSerializable
{
    use SearchTrait;

    public function __construct()
    {
        if($_ENV['APP_ENV'] == 'prod' && empty($_ENV['CHROMA_SERVICE_URL'])) {
            throw new Exception('CHROMA_SERVICE_URL not set');
        }
    }

    public function execute(ManagerRegistry $doctrine)
    {
        set_time_limit(60);

        $this->filterLocal = !empty($this->lat) && !empty($this->lng);
        $this->filterProvincial = !empty($this->province);

        $this->doctrine = $doctrine;
        $body = new RemoteSearchParams();
        $body->query = $this->q;

        $conn = Database::getNativeConnection();

        $sql = "SET SESSION group_concat_max_len=18446744073709551615";
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        if($this->filterLocal) {
            $sql = "CREATE TEMPORARY TABLE distances AS SELECT
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

        $partnerWhereClause = "";
        if(!empty($this->partners)) {
            $partners = array_map(fn ($index) => "resource.partner = :partner$index", array_keys($this->partners));
            $partnerWhereClause = "AND(" . implode(" OR ", $partners) . ")";
        }

        $sql = "CREATE TEMPORARY TABLE potential_resources AS
                    SELECT id FROM resource
                    WHERE
                      computed_canonical_record_id IS null
                      AND is_deleted = 0
                      $partnerWhereClause
        ";
        if(!empty($this->province)) {
            $localSql = $this->filterLocal ? "(delivery = 'local' OR delivery = 'regional') AND id IN (SELECT id FROM distances)" : "(delivery = 'local' OR delivery = 'regional')";
            $provincialSql = $this->filterProvincial ? "delivery = 'provincial' AND id IN (SELECT resource_id FROM delivery_province WHERE delivery_province.abbreviated_name = :province)" : "delivery = 'provincial'";
            $sql .= "
                      AND (
                        delivery = 'national'
                        OR ($provincialSql)
                        OR ($localSql)
                        OR (delivery = '')
                      )
            ";
        }
        if (!empty($this->delivery)) {
            $sql .= " AND ( ";
            foreach ($this->delivery as $index => $deliveryType) {
                if ($index !== (count($this->delivery) - 1)) {
                    $sql .= "delivery = :delivery$index OR ";
                    continue;
                }
                $sql .= "delivery = :delivery$index";
            }
            $sql .= " )";
        }
        $stmt = $conn->prepare($sql);
        if(!empty($this->province)) {
            $stmt->bindValue(":province", $this->province);
        }
        if(!empty($this->partners)) {
            foreach($this->partners as $index => $partner) {
                $stmt->bindValue(":partner{$index}", $partner);
            }
        }
        if (!empty($this->delivery)) {
            foreach ($this->delivery as $index => $deliveryType) {
                $stmt->bindValue(":delivery{$index}", $deliveryType);
            }
        }
        $stmt->execute();
        $stmt->closeCursor();

        $this->executeCommonPortion($body, $doctrine, $conn);
    }
}
