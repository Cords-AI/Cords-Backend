<?php

namespace App\Features\LinkMetaData;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:update:links')]
class UpdateLinkMetaDataCommand extends Command
{
    private ManagerRegistry $doctrine;

    public function __construct(
        ManagerRegistry $doctrine,
        string $name = null
    ) {
        parent::__construct($name);
        $this->doctrine = $doctrine;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->doctrine->getManager();

        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();
        $qb->select("r")
            ->from("App\Resource\Resource", "r")
            ->where("r.websiteEn != ''")
            ->andWhere("r.computedCanonicalRecordId = ''")
            ->andWhere("r.linkMetaDataUpdateDate IS null")
            ->orderBy("r.linkMetaDataUpdateDate", "ASC")
        ;
        //        $qb->setMaxResults(100);
        $resources = $qb->getQuery()->getResult();
        $count = count($resources);

        foreach ($resources as $key => $resource) {
            $url = $resource->getWebsite();
            if(!str_starts_with($url, "http")) {
                $url = "http://$url";
            }
            $index = $key + 1;
            print "$index of $count - $url";
            $result = shell_exec("node ./crawler/crawler.mjs $url");
            $linkMetaData = json_decode($result);
            if(empty($linkMetaData->error)) {
                $resource->setLinkMetaData($linkMetaData);
                $resource->setLinkMetaDataUpdateDate(time());
                $em->persist($resource);
                $em->flush();
                print " ✅ ";
            } else {
                print " ❌ ";
            }
            print "\n";
        }

        return Command::SUCCESS;
    }
}
