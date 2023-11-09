<?php

namespace App\Search;

use App\Database;

class SQLContentSearchStrategy implements ContentSearchStrategyInterface
{
    public function execute(string $q, RemoteSearchParams $body): array
    {
        $conn = Database::getNativeConnection();
        $ids = $body->ids ?? "";
        $sql = "
            SELECT id
                FROM
                    resource
                WHERE
                    MATCH (
                        name_en,
                        name_fr,
                        description_en,
                        description_fr
                    ) AGAINST(:q IN NATURAL LANGUAGE MODE) > 0
                    AND id IN ($ids)
            ";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(":q", $q);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $results = [];
        foreach($rows as $row) {
            $results[] = new Result(id: $row['id']);
        }
        return $results;
    }
}
