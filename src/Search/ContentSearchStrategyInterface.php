<?php

namespace App\Search;

interface ContentSearchStrategyInterface
{
    /** @return array<Result>  */
    public function execute(string $q, RemoteSearchParams $body): array;
}
