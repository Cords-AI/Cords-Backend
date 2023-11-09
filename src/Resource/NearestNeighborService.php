<?php

namespace App\Resource;

use App\Database;
use App\Search\ContentSearchStrategyInterface;
use App\Search\RemoteSearchParams;
use GuzzleHttp\Client;

class NearestNeighborService implements ContentSearchStrategyInterface
{
    private string $currentResourceId;
    private string $currentResourceDelivery;
    private ?float $lat;
    private ?float $lng;

    public function getNeighbours(): ?array
    {
        $conn = Database::getNativeConnection();

        $sql = "SET SESSION group_concat_max_len=18446744073709551615;
                CREATE TEMPORARY TABLE potential_neighbors AS
                    SELECT resource.id
                    FROM resource
                             LEFT JOIN address ON resource.id = address.resource_id
                             LEFT JOIN delivery_province as provinces ON resource.id = provinces.resource_id
                    WHERE computed_canonical_record_id IS null
                      AND is_deleted = 0
                      AND resource.id != :currentResourceId
                      AND (ST_Distance_Sphere(point(:lng, :lat), point(address.lng, address.lat)) < 10000
                        OR (resource.delivery != 'local' AND resource.delivery != 'regional'))
                      AND (JSON_OVERLAPS((SELECT JSON_ARRAYAGG(delivery_province.full_name)
                                          FROM delivery_province
                                          WHERE delivery_province.resource_id = :currentResourceId),
                                         (SELECT JSON_ARRAYAGG(delivery_province.full_name)
                                          FROM delivery_province
                                          WHERE delivery_province.resource_id = resource.id)) OR
                           resource.delivery != 'provincial')
                     AND address.type = 'physical'
                     AND resource.delivery = :currentResourceDelivery
                    GROUP BY resource.id";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('currentResourceId', $this->currentResourceId);
        $stmt->bindValue('lat', $this->lat);
        $stmt->bindValue('lng', $this->lng);
        $stmt->bindValue('currentResourceDelivery', $this->currentResourceDelivery);
        $stmt->execute();

        $sql = "SELECT GROUP_CONCAT(CONCAT(\"'\", id, \"'\")) FROM potential_neighbors";

        $stmt = $conn->prepare($sql);
        $stmt->execute();

        $chromaPostBody = new RemoteSearchParams();
        $chromaPostBody->ids = $stmt->fetchColumn();

        if (!$chromaPostBody->ids) {
            return [];
        }

        return $this->execute($sql, $chromaPostBody);
    }

    public function id(string $id): self
    {
        $this->currentResourceId = $id;
        return $this;
    }

    public function delivery(string $delivery): self
    {
        $this->currentResourceDelivery = $delivery;
        return $this;
    }

    public function lat(?float $lat): self
    {
        $this->lat = $lat;
        return $this;
    }

    public function lng(?float $lng): self
    {
        $this->lng = $lng;
        return $this;
    }

    public function execute(string $q, RemoteSearchParams $body): array
    {
        $url = "{$_ENV['CHROMA_SERVICE_URL']}/neighbors?id={$this->currentResourceId}";

        $request = new \GuzzleHttp\Psr7\Request('POST', $url, [], json_encode($body));
        $client = new Client();
        $result = $client->send($request);
        $contents = json_decode($result->getBody());

        return current($contents->ids);
    }
}
