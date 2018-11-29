<?php
/**
 * -------------------------
 *
 * -------------------------
 * User: shulu
 * Date: 2018/11/29
 * Time: 16:07
 */

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

require_once 'vendor/autoload.php';
$config = require_once 'application/database.php';
$isDevMoe = true;
$configuration =  Setup::createAnnotationMetadataConfiguration(array(__DIR__. '/application/entity'), $isDevMoe);
$conn = array(
	'driver'   => 'pdo_mysql',
	'host'   => $config['hostname'] ? $config['hostname'] : '127.0.0.1',
	'user'     => $config['username'] ? $config['username'] : 'root',
	'password' => $config['password'] ? $config['password'] : '123456',
	'dbname'   => $config['database'] ? $config['database'] : 'oneschool',
	'port' => $config['hostport'] ? $config['hostport'] : 3306,
	'charset' => 'utf8'
);
$entityManager = EntityManager::create($conn, $configuration);
return \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($entityManager);