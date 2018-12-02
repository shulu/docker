<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2017/8/3
 * Time: 下午1:50
 */

namespace Lychee\Module\ExtraMessage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="em_client_version", schema="ciyo_extramessage")
 * Class EMClientVersion
 * @package Lychee\Module\ExtraMessage\Entity
 */
class EMClientVersion {

	const TYPE_IOS = 1;
	const TYPE_ANDROID = 2;
	const TYPE_ANDROID_BILIBILI = 3;
	const TYPE_ANDROID_DMZJ = 4;

	/**
	 * @var int
	 * @ORM\Id
	 * @ORM\Column(name="id", type="bigint")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	public $id;

	/**
	 * @var integer
	 * @ORM\Column(name="type", type="integer")
	 */
	public $type;

	/**
	 * @var string
	 * @ORM\Column(name="version", type="string", length=64)
	 */
	public $version;

	/**
	 * @var int
	 * @ORM\Column(name="code", type="integer")
	 */
	public $code;

	/**
	 * @var string
	 * @ORM\Column(name="desc", type="string", length=255)
	 */
	public $desc;

	/**
	 * @var string
	 * @ORM\Column(name="url", type="string", length=255)
	 */
	public $url;

	/**
	 * @var bool
	 * @ORM\Column(name="force_update", type="boolean")
	 */
	public $forceUpdate;

}