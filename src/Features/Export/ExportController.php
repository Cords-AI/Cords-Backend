<?php

namespace App\Features\Export;

use App\Search\SearchCollection;
use BackendKit\Response\CsvResponse;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExportController extends AbstractController
{
    public function __construct(private readonly ManagerRegistry $doctrine)
    {
        ini_set('memory_limit', '1024M');
    }

    #[GET('/export/taxonomy')]
    public function getTaxonomy(TrainingDataService $data): CsvResponse
    {
        return new CsvResponse($data->getTerms());
    }

    #[GET('/export/training-data')]
    public function exportTrainingData(TrainingDataService $data): CsvResponse
    {
        $csvForAllTerms = $data->getAllTrainingData();

        return new CsvResponse($csvForAllTerms);
    }

    #[GET('/export/training-data/{term}')]
    public function getTrainingData($term, TrainingDataService $data): CsvResponse
    {
        $rows = $data->getTrainingData($term);
        return new CsvResponse($rows);
    }

    #[GET('/export')]
    public function get(ManagerRegistry $doctrine, Request $request)
    {
        $limit = intval($_REQUEST['limit'] ?? 2000);
        if(!$limit || $limit > 2000) {
            $limit = 2000;
        }
        $offset = intval($_REQUEST['offset'] ?? 0);
        $data = $this->getBody($limit, $offset);
        $data['meta'] = ['total' => $this->getTotalResources()];
        return new JsonResponse($data);
    }

    #[GET('/export/records')]
    public function getRecords(ManagerRegistry $doctrine, Request $request)
    {
        $ids = $request->get('ids');
        $ids = explode(',', $ids);
        $collection = new SearchCollection($ids, $doctrine);
        return new JsonResponse($collection->value);
    }

    #[POST('/export')]
    public function post(ManagerRegistry $doctrine, HttpClientInterface $client)
    {
        $result = $client->request('POST', "{$_ENV['CORDS_SEARCH_URL']}/full_upload", [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => 60 * 60,
            'body' => json_encode($this->getBody())
        ]);

        return new JsonResponse(["Search service status {$result->getStatusCode()}"], $result->getStatusCode());
    }

    private function getBody($limit = null, $offset = null): array
    {
        return [
            'records' => $this->getResources($limit, $offset)
        ];
    }

    private function getTotalResources(): int
    {
        $sql = "
SELECT
    COUNT(*)
FROM resource
LEFT JOIN address ON address.resource_id = resource.id
WHERE (computed_canonical_record_id IS NULL OR computed_canonical_record_id = '') AND resource.is_deleted IS NOT TRUE
ORDER BY resource.id ASC
";
        $conn = $this->doctrine->getConnection();
        $stmt = $conn->executeQuery($sql);
        return $stmt->fetchOne();
    }

    private function getResources($limit, $offset): array
    {
        $sql = "
SELECT
    JSON_OBJECT(
            'recordId', resource.id,
            'partner_id', partner_id,
            'partner', partner
        ) as payload,
    CONCAT(
        COALESCE(name_en, ''), COALESCE('\n'),
        COALESCE(name_fr, ''), COALESCE('\n'),
        COALESCE(description_en, ''), COALESCE('\n'),
        COALESCE(description_fr, ''), COALESCE('\n'),
        COALESCE(body_en, ''), COALESCE('\n'),
        COALESCE(body_fr, ''), COALESCE('\n'),
        COALESCE(JSON_UNQUOTE(JSON_EXTRACT(taxonomy_en, '$.*')), ''), COALESCE('\n'),
        COALESCE(JSON_UNQUOTE(JSON_EXTRACT(taxonomy_fr, '$.*')), '')
    ) AS searchText,
    CONCAT(
        '[', address.lat, ',', address.lng, ']'
    ) AS coordinates
FROM resource
LEFT JOIN address ON address.resource_id = resource.id
WHERE (computed_canonical_record_id IS NULL OR computed_canonical_record_id = '') AND resource.is_deleted IS NOT TRUE
ORDER BY resource.id ASC
";

        if($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }

        /** @var Connection $conn */
        $conn = $this->doctrine->getConnection();
        $stmt = $conn->executeQuery($sql);
        $result = $stmt->fetchAllAssociative();

        $result = array_map(function ($a) {
            $a['coordinates'] = json_decode($a['coordinates']);
            if(!$a['coordinates']) {
                $a['coordinates'] = [0, 0];
            }
            return $a;
        }, $result);

        return $result;
    }
}
