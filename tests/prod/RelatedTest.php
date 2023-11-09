<?php

use App\Search\RelatedSearchService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RelatedTest extends KernelTestCase
{
    /** INFO: Manget resource with a national delivery */
    const MAGNET_ACCOUNT_MANAGER_RESOURCE_ID = "ebe1f825-e218-4776-9620-86d473ba330c";

    public function testGetRelatedWithPrompt()
    {
        $this->expectNotToPerformAssertions();

        $container = static::getContainer();
        $doctrine = $container->get("doctrine");

        $searchService = new RelatedSearchService();

        $searchService
            ->q("employment assistance Account Manager")
            ->lat(60.108669)
            ->lng(-113.642578)
            ->offset(1)
            ->currentResourceDelivery('national')
            ->provinceFromSearch('ON')
            ->showDistances(true)
            ->pageSize(5)
            ->currentResourceId(self::MAGNET_ACCOUNT_MANAGER_RESOURCE_ID)
            ->execute($doctrine);

        $searchService->getResources();
    }
}
