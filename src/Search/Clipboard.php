<?php

namespace App\Search;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;

class Clipboard implements \JsonSerializable
{
    private $limit = 10;

    private $total;

    private array $ids;

    public function __construct(private ManagerRegistry $doctrine, $ids, $offset = 0)
    {
        $sql = "SELECT id FROM resource WHERE id IN(:idsToFetch) ORDER BY FIELD(id, :idsToFetch) LIMIT :offset, :limit";

        /** @var \Doctrine\DBAL\Connection $conn **/
        $conn = $doctrine->getConnection();

        $stmt = $conn->executeQuery(
            $sql,
            [
                'idsToFetch' => $ids,
                'offset' => $offset,
                'limit' => $this->limit
            ],
            [
                'idsToFetch' => Connection::PARAM_STR_ARRAY,
                'offset' => \PDO::PARAM_INT,
                'limit' => \PDO::PARAM_INT,
            ]
        );

        $result = $stmt->fetchAllAssociative();
        $this->ids = array_map(fn ($row) => $row['id'], $result);

        $sql = "SELECT COUNT(*) AS total FROM resource WHERE id IN(:idsToFetch)";

        $total = $doctrine->getConnection()->executeQuery(
            $sql,
            ['idsToFetch' => $ids],
            ['idsToFetch' => Connection::PARAM_STR_ARRAY]
        )->fetchAllAssociative();

        $this->total = current($total)['total'];
    }

    public function jsonSerialize(): mixed
    {
        $qb = $this->doctrine->getManager()->createQueryBuilder();
        $qb->select('r')
            ->from('App\Resource\Resource', 'r')
            ->where("r.id IN(:ids)")
            ->setParameter('ids', $this->ids)
        ;

        $q = $qb->getQuery();
        $rows = $q->getResult();
        $rows = array_map(fn ($id) => current(array_filter($rows, fn ($r) => $r->getId() == $id)), $this->ids);

        return [
            'meta' => [
                'total' => $this->total
            ],
            'data' => $rows
        ];
    }
}
