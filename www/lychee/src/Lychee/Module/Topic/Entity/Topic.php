<?php
namespace Lychee\Module\Topic\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="topic", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="title_udx", columns={"title"})
 * }, indexes={
 *   @ORM\Index(name="creator_idx", columns={"creator_id"})
 * })
 */
class Topic {

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var int
     *
     * @ORM\Column(name="creator_id", type="bigint", nullable=true)
     */
    public $creatorId;

    /**
     * @var int
     *
     * @ORM\Column(name="manager_id", type="bigint", nullable=true)
     */
    public $managerId;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=60)
     */
    public $title;

    /**
     * @var string
     *
     * @ORM\Column(name="summary", type="string", length=100, nullable=true)
     */
    public $summary;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=1000, nullable=true)
     */
    public $description;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_time", type="datetime")
     */
    public $createTime;

    /**
     * @var string
     *
     * @ORM\Column(name="index_image_url", type="string", length=2083, nullable=true)
     */
    public $indexImageUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="cover_image_url", type="string", length=2083, nullable=true)
     */
    public $coverImageUrl;

    /**
     * @var int
     *
     * @ORM\Column(name="post_count", type="integer")
     */
    public $postCount = 0;

    /**
     * @var bool
     *
     * @ORM\Column(name="deleted", type="boolean")
     */
    public $deleted = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="hidden", type="boolean", options={"default":"0"})
     */
    public $hidden = false;

    /**
     * @var string
     *
     * @ORM\Column(name="op_mark", type="string", length=20, nullable=true)
     */
    public $opMark;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="link_title", type="string", length=255, nullable=true)
	 */
	public $linkTitle;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="link", type="string", length=255, nullable=true)
	 */
	public $link;

    /**
     * @var int
     *
     * @ORM\Column(name="follower_count", type="integer")
     */
    public $followerCount = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="follower_position", type="integer")
     */
    public $followerPosition = 0;

    /**
     * @var bool
     *
     * @ORM\Column(name="apply_to_follow", type="boolean", options={"default":0})
     */
    public $applyToFollow = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="private", type="boolean", options={"default":0})
     */
    public $private = false;

    /**
     * @var string
     *
     * @ORM\Column(name="color", type="string", length=10, nullable=true)
     */
    public $color;

    /**
     * @var bool
     *
     * @ORM\Column(name="certified", type="boolean", options={"default":0})
     */
    public $certified = false;
}