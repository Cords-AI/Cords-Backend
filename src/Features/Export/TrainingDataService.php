<?php

namespace App\Features\Export;

use Doctrine\Persistence\ManagerRegistry;

class TrainingDataService
{
    public function __construct(private readonly ManagerRegistry $doctrine)
    {
    }

    public function getTerms()
    {
        /** @var \Doctrine\DBAL\Connection $conn */
        $conn = $this->doctrine->getConnection();
        $rows = $conn->executeQuery("SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(taxonomy_en, '$.taxonomyTerm')) FROM resource LIMIT 0,100")->fetchAllNumeric();
        $terms = array_reduce($rows, function ($value, $a) {
            $values = array_filter(explode("; ", current($a)), fn ($b) => $b);
            return array_merge($value, $values);
        }, []);
        $terms = array_unique($terms);
        sort($terms);
        return array_map(fn ($a) => [$a, $this->getTaxonomyCount($a)], $terms);
    }

    private function getTaxonomyCount($term)
    {
        /** @var \Doctrine\DBAL\Connection $conn */
        $conn = $this->doctrine->getConnection();
        $stmt = $conn->prepare('SELECT COUNT(*) FROM resource WHERE JSON_EXTRACT(taxonomy_en, "$.taxonomyTerm") LIKE :term');
        $stmt->bindValue('term', "%$term;%");
        return $stmt->executeQuery()->fetchOne();
    }

    public function getTrainingData($term)
    {
        /** @var \Doctrine\DBAL\Connection $conn */
        $conn = $this->doctrine->getConnection();
        $stmt = $conn->prepare('SELECT name_en, description_en FROM resource WHERE JSON_EXTRACT(taxonomy_en, "$.taxonomyTerm") LIKE :term LIMIT 0, 1000');
        $stmt->bindValue('term', "%$term;%");
        $result = $stmt->executeQuery()->fetchAllAssociative();
        return array_map(function ($a) use ($term) {
            $item = [];
            $item[] = $a['name_en'] . " " . preg_replace("/\r?\n/", " ", $a['description_en']);
            $item[] = $term;
            return $item;
        }, $result);
    }

    public function getAllTrainingData(): array
    {
        /** @var \PDO $pdo */
        $pdo = $this->doctrine->getConnection()->getNativeConnection();

        $requiredTopicCount = 100;

        $q = "SELECT topic,
                       JSON_ARRAYAGG(
                               JSON_OBJECT(
                                       'label', topic,
                                       'text', CONCAT_WS(' ',
                                                         IF(name_en = '', name_fr, name_en),
                                                         IF(description_en = '', description_fr, description_en)
                                           )
                                   )
                           ) as taxonomyList
                FROM (SELECT topic,
                             name_en,
                             name_fr,
                             description_en,
                             description_fr,
                             computed_canonical_record_id,
                             @rownum := IF(@prev = topic COLLATE utf8mb4_unicode_ci, @rownum + 1, 1) AS rowsForThisTopic,
                             @prev := topic COLLATE utf8mb4_unicode_ci
                      FROM resource,
                           (SELECT @rownum := 0, @prev := '') AS vars
                      WHERE topic IS NOT NULL
                        AND topic != ''
                        AND computed_canonical_record_id IS NULL
                      ORDER BY topic) as subquery
                WHERE rowsForThisTopic <= $requiredTopicCount
                  AND computed_canonical_record_id IS NULL
                GROUP BY topic
                HAVING COUNT(topic) >= $requiredTopicCount";

        $stmt = $pdo->prepare($q);
        $stmt->execute();
        $results = $stmt->fetchAll();

        $csvResults = [['text', 'label']];

        foreach ($results as $taxonomyArray) {
            $taxonomyArray = json_decode($taxonomyArray['taxonomyList'], true);

            array_map(function ($taxonomy) use (&$csvResults) {
                $taxonomy['text'] = strip_tags(str_replace(['<', '>'], [' <', '> '], $taxonomy['text']));
                $taxonomy['text'] = preg_replace("/(\\n|\\r)/", " ", $taxonomy['text']);
                $taxonomy['text'] = preg_replace("/\s+/", ' ', $taxonomy['text']);
                $csvResults[] = [$taxonomy['text'], $taxonomy['label']];
            }, $taxonomyArray);
        }

        return $csvResults;
    }
}
