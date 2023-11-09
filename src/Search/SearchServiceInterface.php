<?php

namespace App\Search;

use Doctrine\Persistence\ManagerRegistry;

interface SearchServiceInterface
{
    public function q($value): SearchServiceInterface;
    public function lat($value): SearchServiceInterface;
    public function lng($value): SearchServiceInterface;
    public function province($value): SearchServiceInterface;
    public function showDistances($value): SearchServiceInterface;
    public function offset($value = null): SearchServiceInterface;
    public function execute(ManagerRegistry $doctrine);
    public function partners(array $partners);
    public function delivery(array $delivery);
    public function pageSize($size);
}
