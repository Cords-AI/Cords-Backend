<?php

namespace App;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use PDO;

class Database
{
    public static function getNativeConnection(): PDO
    {
        return self::doctrine()->getConnection()->getNativeConnection();
    }

    public static function getConnection(): Connection
    {
        return self::doctrine()->getConnection();
    }

    public static function doctrine(): Registry
    {
        $app = $GLOBALS['app'];
        return $app->getContainer()->get('doctrine');
    }
}
