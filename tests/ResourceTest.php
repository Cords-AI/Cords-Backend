<?php

use App\Resource\ResourceController;
use App\Resource\ResourceService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ResourceTest extends KernelTestCase
{
    public function testGetRelated()
    {
        $container = static::getContainer();
        $doctrine = $container->get("doctrine");

        $controller = new ResourceController();
        $controller->getRelated(new ResourceService($doctrine), $_ENV['TEST_RESOURCE_ID']);
    }
}
