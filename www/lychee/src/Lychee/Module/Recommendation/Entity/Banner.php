<?php
namespace Lychee\Module\Recommendation\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="recommendation_banner")
 */
class Banner {
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    public $id;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="integer", nullable=true)
     */
    public $position;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=2083)
     */
    public $url;

    /**
     * @var string
     *
     * @ORM\Column(name="image_url", type="string", length=2083)
     */
    public $imageUrl;

    /**
     * @var int
     *
     * @ORM\Column(name="image_width", type="integer")
     */
    public $imageWidth;

    /**
     * @var int
     *
     * @ORM\Column(name="image_height", type="integer")
     */
    public $imageHeight;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=20)
     */
    public $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=200)
     */
    public $description;

    /**
     * @var string
     *
     * @ORM\Column(name="share_title", type="string", length=20, nullable=true)
     */
    public $shareTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="share_text", type="string", length=1000, nullable=true)
     */
    public $shareText;

    /**
     * @var string
     *
     * @ORM\Column(name="share_image_url", type="string", length=2083, nullable=true)
     */
    public $shareImageUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="share_big_image_url", type="string", length=2083, nullable=true)
     */
    public $shareBigImageUrl;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="views", type="integer", options={"default":"0"})
	 */
	public $views = 0;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="unique_views", type="integer", options={"default":"0"})
	 */
	public $uniqueViews = 0;
}