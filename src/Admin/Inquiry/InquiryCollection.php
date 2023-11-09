<?php

namespace App\Admin\Inquiry;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

class InquiryCollection implements \JsonSerializable
{
    private int $total;

    private array $data = [];

    private int $offset = 0;

    private int $limit = 50;

    private string $order = 'i.createdDate';

    private string $dir = 'DESC';

    public function __construct(
        private ManagerRegistry $doctrine
    ) {
    }

    public function offset($value): self
    {
        if ($value) {
            $this->offset = $value;
        }
        return $this;
    }

    public function limit($value): self
    {
        if ($value) {
            $this->limit = $value;
        }
        return $this;
    }

    public function order($value): self
    {
        if ($value === 'resource') {
            $this->order = "JSON_EXTRACT(r.name, '$.en')";
        }
        return $this;
    }

    public function dir($value): self
    {
        if ($value) {
            $this->dir = $value;
        }
        return $this;
    }

    public function build(): self
    {
        $em = $this->doctrine->getManager();

        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();

        $qb->select('i')
            ->from('App\Resource\Inquiry', 'i')
            ->join('i.resource', 'r')
            ->setFirstResult($this->offset)
            ->setMaxResults($this->limit)
            ->orderBy($this->order, $this->dir)
        ;

        $q = $qb->getQuery();
        $paginator = new Paginator($q);

        $this->total = count($paginator);

        foreach ($paginator as $row) {
            $this->data[] = $row;
        }

        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return [
            "meta" => [
                "total" => $this->total
            ],
            "data" => $this->data
        ];
    }
}
