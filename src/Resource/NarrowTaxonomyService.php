<?php

namespace App\Resource;

use App\Database;

class NarrowTaxonomyService
{
    public ?array $narrowTaxonomyIds = [];
    public ?string $narrowTaxonomyTerm = '';

    public function executeNarrowTaxonomyQuery(string $id): void
    {
        $conn = Database::getNativeConnection();

        $sql = "SELECT resource.id                          AS resourceId,
       outerProvinces.outerProvinceNames    AS originalResourceProvinces,
       SUBSTRING_INDEX(JSON_UNQUOTE(JSON_EXTRACT(COALESCE(resource.taxonomy_en,  resource.taxonomy_fr), '$.taxonomyTerms')), ' - ', -1) AS narrowTaxonomy,
       (SELECT JSON_ARRAYAGG(
                        similarId
                   )
        FROM (SELECT tempResource.id       AS similarId,
                     tempResource.delivery AS similarDelivery,
                     created_date          AS similarCreated,
                     ST_Distance_Sphere(
                             point(addressCurrent.lng, addressCurrent.lat),
                             point(address.lng, address.lat)
                         )                 AS distance
              FROM resource AS tempResource
                       LEFT JOIN
                   (SELECT *
                    FROM address
                    WHERE type = 'physical') AS address ON tempResource.id = address.resource_id
                       LEFT JOIN
                   address AS addressCurrent ON resource.id = addressCurrent.resource_id
              WHERE addressCurrent.type = 'physical'
                AND SUBSTRING_INDEX(JSON_UNQUOTE(JSON_EXTRACT(COALESCE(tempResource.taxonomy_en,  tempResource.taxonomy_fr), '$.taxonomyTerms')), ' - ', -1) = narrowTaxonomy
                AND tempResource.delivery = resource.delivery
                AND tempResource.id != resource.id
                AND tempResource.computed_canonical_record_id IS NULL
                AND tempResource.is_deleted = 0
                AND (JSON_OVERLAPS(outerProvinces.outerProvinceNames,
                                   (SELECT JSON_ARRAYAGG(full_name)
                                    FROM delivery_province
                                    WHERE delivery_province.resource_id = tempResource.id)) OR
                     resource.delivery != 'provincial')
                AND (ST_Distance_Sphere(
                             point(addressCurrent.lng, addressCurrent.lat),
                             point(address.lng, address.lat)
                         ) < 10000
                     OR (resource.delivery != 'local' AND resource.delivery != 'regional'))
              ORDER BY CASE WHEN distance IS NULL THEN 1 ELSE 0 END,
                       distance,
                       tempResource.created_date DESC
              LIMIT 5) AS similarResources) AS similarRecords

FROM resource
         LEFT JOIN
     (SELECT resource_id, JSON_ARRAYAGG(full_name) AS outerProvinceNames
      FROM delivery_province
      GROUP BY resource_id) AS outerProvinces ON resource.id = outerProvinces.resource_id
WHERE resource.id = :currentResourceId;";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('currentResourceId', $id);
        $stmt->execute();
        $results = $stmt->fetchAll();

        $this->narrowTaxonomyIds = json_decode(current($results)['similarRecords']) ?? [];
        $this->narrowTaxonomyTerm = current($results)['narrowTaxonomy'];
    }

}
