<?php

namespace App\Resource;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;

class ResourceService
{
    private Connection $conn;

    public function __construct(private ManagerRegistry $doctrine)
    {
        $this->conn = $doctrine->getConnection();
    }

    public function getRelated($id)
    {
        $repo = new ResourceRepository($this->doctrine);
        $resource = current(($repo)->findBy(['id' => $id]));

        $sql = <<<SQL
            SELECT
                topics,
                weight
            FROM
                related_topics
            WHERE JSON_CONTAINS(topics, :topic, '$')
            ORDER BY weight DESC;
        SQL;

        $q = $this->conn->prepare($sql);
        $topic = strtolower($resource->getTopic());
        $q->bindValue(":topic", "\"$topic\"");

        $rows = $q->executeQuery()->fetchAllAssociative();

        $relatedTopics = 0;
        $relatedResources = 0;

        foreach($rows as $key => $row) {
            $topics = json_decode($row["topics"]);
            $relatedTopic = current(
                array_filter($topics, fn ($x) => $x != $topic)
            );
            $related = $this->getRelatedForTopic($resource, $relatedTopic);
            if(!count($related)) {
                continue;
            }

            $relatedTopics++;
            $relatedResources += count($related);
            $rows[$key]['related'] = $related;

            // Enough related resources found
            if($relatedTopics >= 5 && $relatedResources >= 5) {
                break;
            }
        }

        if(!$relatedResources) {
            return [];
        }

        $collections = array_filter($rows, fn ($collection) => !empty($collection['related']));
        $result = [];

        while(count($result) < 5) {
            $atLeastOneElementExtracted = false;

            foreach($collections as &$collection) {
                if(!empty($collection['related'])) {
                    $result[] = array_shift($collection['related']);
                    $atLeastOneElementExtracted = true;

                    if(count($result) >= 5) {
                        break;
                    }
                }
            }

            if (!$atLeastOneElementExtracted) {
                break;
            }
        }

        $resources = $repo->findBy(['id' => $result]);
        $result = array_map(fn ($id) => current(array_filter($resources, fn ($r) => $r->getId() == $id)), $result);

        return $result;
    }

    private function getRelatedForTopic(Resource $resource, $topic): ?array
    {
        $sql = <<<SQL

            -- Setup temporary table in order to use WHERE on computed fields
            WITH myResources AS (
                SELECT
                    resource.id as id,
                    -- distance: only for local and regional resources
                    CASE
                        WHEN resource.delivery NOT IN ('local', 'regional') THEN NULL
                        ELSE ST_Distance_Sphere(point(:lng, :lat), point(address.lng, address.lat))
                    END AS distance,
                    -- provinces
                    (SELECT JSON_ARRAYAGG(abbreviated_name) FROM delivery_province WHERE resource_id = resource.id) AS provinces,
                    delivery
                FROM
                    resource
                LEFT JOIN address ON resource.id = address.resource_id AND address.type = 'physical'
                WHERE
                    topic = :topic
                    AND resource.id != :id
                    AND delivery = :delivery
                ORDER BY distance IS NULL, distance ASC
            )

            -- Query temporary table
            SELECT
                id
            FROM
                myResources
            WHERE
                -- when delivery is local or regional distance must be within 10km.
                ((delivery != 'local' AND delivery != 'regional') OR distance <= 10000)
                -- when delivery is provincial, check the province
                AND (delivery != 'provincial' OR JSON_OVERLAPS(:provinces, provinces))
            LIMIT 0, 5

        SQL;

        $q = $this->conn->prepare($sql);
        $q->bindValue(":id", $resource->getId());
        $q->bindValue(":topic", $topic);
        $q->bindValue(":delivery", $resource->getDelivery());

        /** @var Address $address */
        $address = $resource->getAddresses()->filter(fn ($row) => $row->getType() == 'physical')->first();
        $q->bindValue(":lat", $address->getLat());
        $q->bindValue(":lng", $address->getLng());

        $provinces = $resource->getDeliveryProvinces()->toArray();
        $provinces = array_map(fn (DeliveryProvince $row) => $row->getAbbreviatedName(), $provinces);
        $q->bindValue(":provinces", json_encode($provinces));

        return $q->executeQuery()->fetchFirstColumn();
    }
}
