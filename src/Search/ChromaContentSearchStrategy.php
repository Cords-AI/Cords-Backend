<?php

namespace App\Search;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class ChromaContentSearchStrategy implements ContentSearchStrategyInterface
{
    public function execute(string $q, RemoteSearchParams $body): array
    {
        $request = new Request('POST', "{$_ENV['CHROMA_SERVICE_URL']}/search?q={$q}", [], json_encode($body));

        $client = new Client();
        $res = $client->send($request);
        $result = json_decode($res->getBody());
        $vectorResults = [];
        foreach($result->ids[0] as $key => $value) {
            $vectorResults[] = new Result($value, vectorDistance: $result->distances[0][$key]);
        }
        return $vectorResults;
    }
}
