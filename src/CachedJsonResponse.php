<?php

namespace App;

use Symfony\Component\HttpFoundation\JsonResponse;

class CachedJsonResponse extends JsonResponse
{
    public function __construct(mixed $data = null, int $status = 200, array $headers = [], bool $json = false)
    {
        parent::__construct($data, $status, $headers, $json);

        if(!empty($_ENV['NO_CACHE']) && $_ENV['NO_CACHE'] === "TRUE") {
            return;
        }

        $this->setPublic();
        $this->setMaxAge(60 * 60 * 24);

    }
}
