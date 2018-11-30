<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2017/9/19
 * Time: 下午2:40
 */

namespace Lychee\Module\ExtraMessage\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EMStory
 *
 * @ORM\Entity
 * @ORM\Table(name="em_story", schema="ciyo_extramessage")
 *
 * @package Lychee\Module\ExtraMessage\Entity
 */

class EMStory {

	/**
	 * @var int
	 *
	 * @ORM\Column(name="id", type="bigint")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	public $id;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="string_id", type="string", length=255)
	 */
	public $stringId;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="title", type="string", length=255)
	 */
	public $title;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="desc", type="string", length=255)
	 */
	public $desc;
}