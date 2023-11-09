<?php

namespace App\Tests;

use App\Features\Import\Magnet\MagnetImporter;
use App\Features\Import\MentorConnector\MentorConnectorImporter;
use App\Features\Import\ProsperBenefits\ProsperBenefitsImporter;
use App\Features\Import\SourcePersistStrategy;
use App\Features\Import\TwoEleven\TwoElevenImporter;
use App\Resource\DeliveryType;
use App\Resource\ResourceRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ImportTest extends KernelTestCase
{
    private ManagerRegistry $doctrine;

    protected function setup(): void
    {
        $container = static::getContainer();
        $this->doctrine = $container->get("doctrine");
    }

    public function testImportMentor()
    {
        $responseBodyString = file_get_contents(__DIR__ . "/fixtures/mentor.json");
        $mockResponse = new MockResponse($responseBodyString);
        $mockHttpClient = new MockHttpClient([$mockResponse, new MockResponse()]);

        $importer = new MentorConnectorImporter($this->doctrine, $mockHttpClient);
        $importer->source = 'mentor';
        $importer->import();

        $repo = new ResourceRepository($this->doctrine);
        $resourceJson = json_decode($responseBodyString)->data[0];
        $resource = $repo->findOneBy(['partnerId' => $resourceJson->id]);

        $this->assertSame($resourceJson->attributes->description->fr, $resource->getDescription()->fr);
        $this->assertSame($resourceJson->attributes->description->en ?? '', $resource->getDescription()->en);

        // national: "Canada" is in the service areas
        $resource = $repo->findOneBy(['partnerId' => 'd7e375b4-906c-4988-b789-71d96f83e7be', 'partner' => 'mentor']);
        $this->assertEquals(DeliveryType::national, $resource->getDelivery());

        // national: "locations" is null
        $resource = $repo->findOneBy(['partnerId' => '1731c03f-c69b-4ae2-ba5b-39af830a5d3e', 'partner' => 'mentor']);
        $this->assertEquals(DeliveryType::national, $resource->getDelivery());

        // provincial
        $resource = $repo->findOneBy(['partnerId' => 'deeb0be2-7157-4e9f-a981-5128dc66fada', 'partner' => 'mentor']);
        $this->assertEquals(DeliveryType::provincial, $resource->getDelivery());

        // local
        $resource = $repo->findOneBy(['partnerId' => 'ddd3349c-ab83-46fa-85b3-2fe4f22afff7', 'partner' => 'mentor']);
        $this->assertEquals(DeliveryType::local, $resource->getDelivery());
    }

    public function testImportMagnet()
    {
        $responseBodyString = file_get_contents(__DIR__ . "/fixtures/magnet.json");
        $mockResponse = new MockResponse($responseBodyString);
        $mockHttpClient = new MockHttpClient([$mockResponse, new MockResponse()]);

        $importer = new MagnetImporter($this->doctrine, $mockHttpClient);
        $importer->source = 'magnet';
        $importer->import();

        $repo = new ResourceRepository($this->doctrine);
        $resourceJson = json_decode($responseBodyString)->data[0];
        $resource = $repo->findOneBy(['partnerId' => $resourceJson->id]);

        $this->assertSame($resourceJson->description, $resource->getDescription()->en);

        // national
        $resource = $repo->findOneBy(['partnerId' => '626165182', 'partner' => 'magnet']);
        $this->assertEquals(DeliveryType::national, $resource->getDelivery());

        // local
        $resource = $repo->findOneBy(['partnerId' => '626165548', 'partner' => 'magnet']);
        $this->assertEquals(DeliveryType::local, $resource->getDelivery());
    }

    public function testImportProsper()
    {
        $responseBodyString = file_get_contents(__DIR__ . "/fixtures/prosper.json");
        $mockResponse = new MockResponse($responseBodyString);
        $mockHttpClient = new MockHttpClient([$mockResponse, new MockResponse()]);

        $importer = new ProsperBenefitsImporter($this->doctrine, $mockHttpClient);
        $importer->source = 'prosper';
        $importer->import();

        $repo = new ResourceRepository($this->doctrine);
        $resourceJson = json_decode($responseBodyString)->items[0];
        $resource = $repo->findOneBy(['partnerId' => $resourceJson->sys->id]);

        $this->assertSame($resourceJson->fields->metaDescription, $resource->getDescription()->en);

        // national
        $resource = $repo->findOneBy(['partnerId' => '2Ys0p8WcVYNfXJ7dkUQmfh', 'partner' => 'prosper']);
        $this->assertEquals(DeliveryType::national, $resource->getDelivery());

        // provincial
        $resource = $repo->findOneBy(['partnerId' => '3XC8Xs46Hyh0ycXma0nZSp', 'partner' => 'prosper']);
        $this->assertEquals(DeliveryType::provincial, $resource->getDelivery());
        $provinces = $resource->getDeliveryProvincesValues();
        $this->assertTrue(count($provinces) == 1 && in_array('YT', $provinces));
    }

    public function testImport211()
    {
        $responseBodyString = file_get_contents(__DIR__ . "/fixtures/211.json");
        $mockResponse = new MockResponse($responseBodyString);
        $mockHttpClient = new MockHttpClient([$mockResponse, new MockResponse()]);

        $importer = new TwoElevenImporter($this->doctrine, $mockHttpClient);
        $importer->source = '211';
        $importer->import();

        $repo = new ResourceRepository($this->doctrine);
        $resourceJson = json_decode($responseBodyString)->Records[0];
        $resource = $repo->findOneBy(['partnerId' => $resourceJson->id]);

        $this->assertSame($resourceJson->Description, $resource->getDescription()->en);

        // national
        $resource = $repo->findOneBy(['partnerId' => '82887851']);
        $this->assertSame(DeliveryType::national, $resource->getDelivery());

        // provincial: single province
        $resource = $repo->findOneBy(['partnerId' => '83562104']);
        $this->assertSame(DeliveryType::provincial, $resource->getDelivery());
        $provinces = $resource->getDeliveryProvincesValues();
        $this->assertTrue(count($provinces) == 1 && in_array('AB', $provinces));

        // local: single city
        $resource = $repo->findOneBy(['partnerId' => '84510327']);
        $this->assertSame(DeliveryType::local, $resource->getDelivery());

        // local: missing service area
        $resource = $repo->findOneBy(['partnerId' => '77129886']);
        $this->assertSame(DeliveryType::local, $resource->getDelivery());

        // regional: multiple cities or regions
        $resource = $repo->findOneBy(['partnerId' => '83572532']);
        $provinces = $resource->getDeliveryProvincesValues();
        $this->assertSame("regional", $resource->getDelivery());
    }

    public function testImport211Ontario()
    {
        $responseBodyString = file_get_contents(__DIR__ . "/fixtures/211-ontario.json");
        $mockResponse = new MockResponse($responseBodyString);
        $mockHttpClient = new MockHttpClient([$mockResponse, new MockResponse()]);

        $importer = new TwoElevenImporter($this->doctrine, $mockHttpClient);
        $importer->source = 'on_en';
        $importer->import();

        $repo = new ResourceRepository($this->doctrine);

        // national: Contains "Canada" in the service areas
        $resource = $repo->findOneBy(['partnerId' => '77007441']);
        $this->assertSame(DeliveryType::national, $resource->getDelivery());

        // provincial: Contains "Ontario" in the service areas
        $resource = $repo->findOneBy(['partnerId' => '62158597']);
        $this->assertSame(DeliveryType::provincial, $resource->getDelivery());
        $provinces = $resource->getDeliveryProvincesValues();
        $this->assertTrue(count($provinces) == 1 && in_array('ON', $provinces));

        // regional: Contains multiple regions or cities in the service areas
        $resource = $repo->findOneBy(['partnerId' => '72031042']);
        $this->assertSame(DeliveryType::regional, $resource->getDelivery());

        // local: Conatins a single location
        $resource = $repo->findOneBy(['partnerId' => '65302785']);
        $this->assertSame(DeliveryType::local, $resource->getDelivery());
    }

    public function testImportSource211()
    {
        $responseBodyString = file_get_contents(__DIR__ . "/fixtures/211.json");
        $mockResponse = new MockResponse($responseBodyString);
        $mockHttpClient = new MockHttpClient([$mockResponse, new MockResponse()]);

        $importer = new TwoElevenImporter($this->doctrine, $mockHttpClient);
        $importer->persistStrategy = new SourcePersistStrategy($this->doctrine, $mockHttpClient);
        $importer->source = '211';
        $importer->import();

        /** @var \PDO $conn **/
        $conn = $this->doctrine->getConnection()->getNativeConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) FROM resource_source WHERE partner_id = :partnerId");
        $stmt->bindValue(":partnerId", "83562104");
        $stmt->execute();
        $count = $stmt->fetchColumn();

        $this->assertEquals(1, $count);
    }
}
