<?php

namespace App\Search;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class SearchCollection
{
    public $value;

    public function __construct(array $ids, ManagerRegistry $doctrine)
    {
        $em = $doctrine->getManager();

        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();
        $qb->select('r')
            ->from('App\Resource\Resource', 'r')
            ->where($qb->expr()->isNull('r.computedCanonicalRecordId'))
            ->andWhere("r.id IN(:ids)")
            ->setParameter('ids', array_values($ids))
        ;

        $q = $qb->getQuery();
        $result = $q->getResult();
        if(!$result) {
            $this->value = [];
            return;
        }

        $mapped = array_map(fn ($id) => current(array_filter($result, fn ($r) => $r->getId() == $id)), $ids);
        $filtered = array_values(array_filter($mapped, fn ($a) => $a !== false));

        $this->value = $filtered;
    }
}
