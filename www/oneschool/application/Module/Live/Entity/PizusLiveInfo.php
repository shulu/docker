<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 29/11/2016
 * Time: 5:08 PM
 */

namespace Lychee\Module\Live\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class PizusLiveInfo
 * @package Lychee\Module\Live\Entity
 * @ORM\Entity()
 * @ORM\Table(name="live_pizus_live_info", schema="ciyo_live")
 */
class PizusLiveInfo {

	/**
	 * @var integer
	 * @ORM\Id
	 * @ORM\Column(name="id", type="bigint")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	public $id;

	/**
	 * @var string
	 * @ORM\Column(name="pid", type="string", length=100)
	 */
	public $pizusId;

	/**
	 * @var string
	 * @ORM\Column(name="live_id", type="string", length=50)
	 */
	public $liveId;

	/**
	 * @var string
	 * @ORM\Column(name="anchor_name", type="string", length=50)
	 */
	public $anchorName;

	/**
	 * @var string
	 * @ORM\Column(name="anchor_avatar", type="string", length=255)
	 */
	public $anchorAvatar;

	/**
	 * @var string
	 * @ORM\Column(name="live_cover", type="string", length=255)
	 */
	public $liveCover;

	/**
	 * @var \DateTime
	 * @ORM\Column(name="recommended_time", type="datetime")
	 */
	public $recommendedTime;
}