<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2017/5/12
 * Time: 上午11:13
 */

namespace Lychee\Module\ExtraMessage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EMPictureRecord
 *
 * @ORM\Entity
 * @ORM\Table(name="em_pic_record", schema="ciyo_extramessage", indexes={
 *   @ORM\Index(name="user_idx", columns={"user_id"})
 * }, uniqueConstraints={
 *   @ORM\UniqueConstraint(name="user_id_udx", columns={"user_id"})
 * })
 *
 * @package Lychee\Module\ExtraMessage\Entity
 */

class EMPictureRecord {

	/**
	 * @var int
	 *
	 * @ORM\Column(name="user_id", type="bigint")
	 * @ORM\Id
	 */
	public $userId;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="record", type="string", length=4096)
	 */
	public $record;
}