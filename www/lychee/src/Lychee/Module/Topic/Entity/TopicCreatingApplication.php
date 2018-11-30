<?php
namespace Lychee\Module\Topic\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="topic_creating_application", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="title_udx", columns={"title"})
 * }, indexes={
 *   @ORM\Index(name="creator_idx", columns={"creator_id"})
 * })
 */
class TopicCreatingApplication {
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="apply_time", type="datetime")
     */
    public $applyTime;

    /**
     * @var int
     *
     * @ORM\Column(name="creator_id", type="bigint", nullable=true)
     */
    public $creatorId;

    /**
     * @var int
     *
     * @ORM\Column(name="category_id", type="integer", nullable=true)
     */
    public $categoryId;

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
}