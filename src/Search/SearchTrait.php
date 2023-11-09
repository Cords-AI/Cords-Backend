<?php

namespace App\Search;

use App\ArrayUtils;
use App\Database;
use App\Resource\DeliveryType;
use App\Resource\Resource;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PDO;

trait SearchTrait
{
    private $q;

    private float $lat;

    private float $lng;

    private string $province;

    private ?string $provinceFromSearch = null;

    private ?string $currentResourceDelivery = null;

    private ?string $currentResourceId = null;

    private $offset = 0;

    private $limit = 10;

    private $total;

    private $idsToFetch = [];

    private ?array $partners;

    private bool $filterLocal;

    private bool $filterProvincial;

    private bool $showDistances = true;

    private array $delivery = [];

    /** @var array<Resource> $resources **/
    private $resources;

    private ManagerRegistry $doctrine;

    private $ids = [];

    public bool $strict = false;

    private int $pageSize = 10;

    private int $maxDistance = 100000;

    public function lat($value): SearchServiceInterface
    {
        $this->lat = floatval($value);

        return $this;
    }

    public function lng($value): SearchServiceInterface
    {
        $this->lng = floatval($value);

        return $this;
    }

    public function q($value): self
    {
        if ($value) {
            $this->q = urldecode($value);
        }
        return $this;
    }

    public function partners(array $partners): SearchServiceInterface
    {
        $this->partners = $partners;

        return $this;
    }

    public function showDistances($value): SearchServiceInterface
    {
        $this->showDistances = $value;
        return $this;
    }

    public function maxDistance($value): SearchServiceInterface
    {
        if($value) {
            $this->maxDistance = $value;
        }
        return $this;
    }

    public function currentResourceDelivery(?string $value): SearchServiceInterface
    {
        if($value) {
            $this->currentResourceDelivery = $value;
        }
        return $this;
    }

    public function currentResourceId(?string $value): SearchServiceInterface
    {
        if($value) {
            $this->currentResourceId = $value;
        }
        return $this;
    }

    public function provinceFromSearch(?string $value): SearchServiceInterface
    {
        $this->provinceFromSearch = $value;
        return $this;
    }

    public function province($value): SearchServiceInterface
    {
        if($value) {
            $this->province = $value;
        }
        return $this;
    }

    public function pageSize($value = null): SearchServiceInterface
    {
        if ($value) {
            $this->pageSize = min($value, 1000);
        }
        return $this;
    }

    public function offset($value = null): SearchServiceInterface
    {
        if ($value) {
            $this->offset = $value;
        }
        return $this;
    }

    public function getResources(): array
    {
        return $this->resources;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'meta' => [
                'total' => $this->total
            ],
            'data' => $this->resources
        ];
    }

    public function idsToFetch(array $idsToFetch): SearchServiceInterface
    {
        return $this;
    }

    /**
     * @param array<Result> $a
     * @param array<Result> $b
     * @return array<Result>
     */
    private function mergeResults(array $a, array $b): array
    {
        $a = ArrayUtils::keyMap($a, 'id');
        $b = ArrayUtils::keyMap($b, 'id');
        foreach($a as $result) {
            if(!isset($b[$result->id])) {
                continue;
            }
            $result->vectorDistance = $b[$result->id]->vectorDistance;
        }
        foreach($b as $result) {
            if(!isset($a[$result->id])) {
                $a[] = $result;
            }
        }
        return array_values($a);
    }

    public function delivery(array $delivery): self
    {
        $this->delivery = $delivery;
        if (!empty($delivery)) {
            if (isset($delivery['national']) && $delivery['national'] === 'true') {
                $this->delivery[] = 'national';
            }
            if (isset($delivery['provincial']) && $delivery['provincial'] === 'true') {
                $this->delivery[] = 'provincial';
            }
            if (isset($delivery['local']) && $delivery['local'] === 'true') {
                $this->delivery[] = 'local';
            }
        }
        return $this;
    }

    private function executeCommonPortion(RemoteSearchParams $body, ManagerRegistry $doctrine, PDO $conn)
    {
        $sql = "SELECT GROUP_CONCAT(CONCAT(\"'\", id, \"'\")) FROM potential_resources";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $body->ids = $stmt->fetchColumn();
        if(!$body->ids) {
            return;
        }

        /** @var PDO $pdo */
        $pdo = $doctrine->getConnection()->getNativeConnection();

        if($this->q) {
            // Query is provided. Filter potential resources
            if(!empty($_ENV['CHROMA_SERVICE_URL'])) {
                $contentSearchStrategy = new ChromaContentSearchStrategy();
            } else {
                $contentSearchStrategy = new SQLContentSearchStrategy();
            }
            $contentResults = $contentSearchStrategy->execute($this->q, $body);

            // Add title matches
            $sql = "
                SELECT
                    resource.id,
                    name_en,
                    (name_en = :q OR name_fr = :q) AS exact,
                    (name_en LIKE :startsWithQ OR name_fr LIKE :startsWithQ) AS startsWith,
                    (name_en LIKE :containsQ OR name_fr LIKE :containsQ) AS contains
                FROM
                    resource
                WHERE
                    (name_en LIKE :containsQ OR name_fr LIKE :containsQ)
                    AND resource.id IN (SELECT id FROM potential_resources)
                ORDER BY
                    exact DESC, startsWith DESC, contains DESC
            ";
            if($this->filterLocal) {
                $sql = "
                    SELECT
                        resource.id,
                        name_en,
                        (name_en = :q OR name_fr = :q) AS exact,
                        (name_en LIKE :startsWithQ OR name_fr LIKE :startsWithQ) AS startsWith,
                        (name_en LIKE :containsQ OR name_fr LIKE :containsQ) AS contains
                    FROM
                        resource
                    LEFT JOIN distances ON resource.id = distances.id
                    WHERE
                        (name_en LIKE :containsQ OR name_fr LIKE :containsQ)
                        AND resource.id IN (SELECT id FROM potential_resources)
                    ORDER BY
                        distance IS NOT NULL DESC, distance ASC, exact DESC, startsWith DESC, contains DESC
                ";
            }
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':q', $this->q);
            $startsWithQ = "{$this->q}%";
            $stmt->bindParam(':startsWithQ', $startsWithQ);
            $containsQ = "%{$this->q}%";
            $stmt->bindParam(':containsQ', $containsQ);
            $stmt->execute();
            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $titleResults = [];
            foreach($matches as $match) {
                $titleResults[] = new Result(
                    $match['id'],
                    exactTitle: $match['exact'],
                    titleStartsWith: $match['startsWith'],
                    titleContains: $match['contains']
                );
            }
            $results = $this->mergeResults($titleResults, $contentResults);
            $this->total = count($results);
            $results = array_slice($results, ($this->offset - 1) * $this->pageSize, $this->pageSize);
        } else {
            // No query provided. Return all potential resources ordered by distance.
            $sql = "SELECT COUNT(*) FROM potential_resources";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $this->total = $stmt->fetchColumn();

            $sql = "
                SELECT potential_resources.id, distance FROM potential_resources
                LEFT JOIN distances ON potential_resources.id = distances.id
                LEFT JOIN resource ON potential_resources.id = resource.id
                ORDER BY
                    resource.delivery = 'local' DESC,
                    distance IS NOT NULL DESC,
                    distance ASC,
                    resource.delivery = 'provincial' DESC,
                    resource.delivery = 'national' DESC
                LIMIT :offset,:limit
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':offset', ($this->offset - 1) * $this->pageSize, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $this->pageSize, PDO::PARAM_INT);
            $stmt->execute();
            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results = [];
            foreach($matches as $match) {
                $results[] = new Result(
                    $match['id'],
                    distance: $match['distance']
                );
            }
        }

        $ids = array_map(fn (Result $a) => $a->id, $results);

        $em = $this->doctrine->getManager();

        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();
        $qb->select('r')
            ->from('App\Resource\Resource', 'r')
            ->where("r.id IN(:ids)")
            ->setParameter('ids', array_values($ids))
        ;

        $q = $qb->getQuery();
        $resources = $q->getResult();

        if($this->filterLocal && $this->showDistances) {
            $localDeliveryResources = array_filter(
                $resources,
                fn (Resource $a) =>
                $a->getDelivery() == DeliveryType::local || $a->getDelivery() == DeliveryType::regional
            );
            $localDeliveryIds = array_map(fn (Resource $a) => $a->getId(), $localDeliveryResources);
            $dConn = Database::getConnection();
            $sql = "SELECT id, distance FROM distances WHERE id IN(:ids)";
            $stmt = $dConn->executeQuery(
                $sql,
                ['ids' => $localDeliveryIds],
                ['ids' => Connection::PARAM_STR_ARRAY]
            );
            $distances = $stmt->fetchAllAssociative();
            foreach($distances as $row) {
                /** @var Result $result */
                $result = current(array_filter($results, fn (Result $a) => $a->id === $row['id']));
                $result->distance = $row['distance'];
            }
        }

        $resources = array_map(function ($id) use ($resources, $results) {
            $resource = current(array_filter($resources, fn ($a) => $a->getId() === $id));
            $result = current(array_filter($results, fn (Result $a) => $a->id === $id));
            $resource->setResult($result);
            return $resource;
        }, $ids);

        $this->resources = $resources;
    }
}
