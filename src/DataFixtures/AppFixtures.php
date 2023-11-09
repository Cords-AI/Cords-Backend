<?php

namespace App\DataFixtures;

use App\Resource\Resource;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $resourceBody = [
            "hours" => '1-5',
            "fees" => '100',
            "twitter" => 'twitter',
            "youtube" => 'youtube',
            "facebook" => 'facebook',
            "linkedin" => 'linkedin',
            "instagram" => 'instagram',
            "languages" => "English",
            "eligibility" => 'all',
            "topics" => 'topics'
        ];

        $resourceTaxonomy = [
            "taxonomyTerm" => 'taxonomy term',
            "taxonomyTerms" => 'taxonomy terms',
        ];

        $resource = new Resource();
        $resource->setPartnerId(1);
        $resource->setNameEn('Canonical Test');
        $resource->setNameFr('Canonical Test French');
        $resource->setDescriptionEn('Canonical Test');
        $resource->setDescriptionFr('Canonical Test French');
        $resource->setCreatedDate(time());
        $resource->setModifiedDate(time());
        $resource->setWebsiteEn('ubriety.com');
        $resource->setWebsiteFr('');
        $resource->setEmailEn('test@example.com');
        $resource->setEmailFr('');
        $resource->setPartner('test record');
        $resource->setBodyEn($resourceBody);
        $resource->setBodyFr($resourceBody);
        $resource->setTaxonomyEn($resourceTaxonomy);
        $resource->setTaxonomyFr($resourceTaxonomy);
        $resource->setDelivery('provincial');

        $manager->persist($resource);

        $resource2 = new Resource();
        $resource2->setPartnerId(2);
        $resource2->setComputedCanonicalRecordId($resource->getId());
        $resource2->setNameEn('Canonical Test');
        $resource2->setNameFr('Canonical Test French');
        $resource2->setDescriptionEn('Canonical Test');
        $resource2->setDescriptionFr('Canonical Test French');
        $resource2->setCreatedDate(time());
        $resource2->setModifiedDate(time());
        $resource2->setWebsiteEn('ubriety.com');
        $resource2->setWebsiteFr('');
        $resource2->setEmailEn('test@example.com');
        $resource2->setEmailFr('');
        $resource2->setPartner('test record');
        $resource2->setBodyEn($resourceBody);
        $resource2->setBodyFr($resourceBody);
        $resource2->setTaxonomyEn($resourceTaxonomy);
        $resource2->setTaxonomyFr($resourceTaxonomy);
        $resource2->setDelivery('provincial');
        $manager->persist($resource2);

        $manager->flush();
    }
}
