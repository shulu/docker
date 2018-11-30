<?php
namespace Lychee\Module\Recommendation\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="app_channel_package", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="code_udx", columns={"code"})
 * })
 */
class AppChannelPackage {
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    public $id;

    /**
     * @var string
     * @ORM\Column(name="code", type="string", length=20)
     */
    public $code;

    /**
     * @var string
     *
     * @ORM\Column(name="link", type="string", length=255)
     */
    public $link;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="update_time", type="datetime")
	 */
	public $updateTime;
}