<?php

namespace App\Tests;

use App\Resource\Resource;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\HttpClient;

class ChromaTest extends KernelTestCase
{
    public function testVectorization(): void
    {
        $resource = new Resource();
        $resource->setBodyEn(file_get_contents(__DIR__ . "/fixtures/alice-in-wonderland.txt"));

        $body = [
            "ids" => ["b951b5e2-9966-41c6-bc53-1591bd30d6b2"],
            "documents" => [$resource->getDocument()]
        ];
        $client = HttpClient::create();
        $response = $client->request('POST', $_ENV["CHROMA_SERVICE_URL"], [
            "headers" => [
                "Content-Type" => "application/json",
            ],
            "body" => json_encode($body)
        ]);
        $this->assertEquals("200", $response->getStatusCode());
    }
}
