<?php
/**
 * -------------------------
 *
 * -------------------------
 * User: shulu
 * Date: 2018/11/29
 * Time: 16:10
 */

namespace app\common\controller;
use think\Config;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

class Entity
{
	public static function getEntityManager()
	{
		$isDevMoe = true;
		$config =  Setup::createAnnotationMetadataConfiguration(array(APP_PATH . '/entity'), $isDevMoe);
		$conn = array(
			'driver'   => 'pdo_mysql',
			'host'   => Config::get('database.hostname') ? Config::get('database.hostname') : '127.0.0.1',
			'user'     => Config::get('database.username') ? Config::get('database.username') : 'root',
			'password' => Config::get('database.password') ? Config::get('database.password') : '123456',
			'dbname'   => Config::get('database.database') ? Config::get('database.database') : 'oneschool',
			'port' => Config::get('database.hostport') ? Config::get('database.hostport') : 3306,
			'charset' => 'utf8'
		);
		return EntityManager::create($conn, $config);
	}
}