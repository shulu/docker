<?php
namespace Lychee\Module\ContentManagement\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="review_state", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="app_channel_idx", columns={"app_id", "channel"})
 * })
 */
class ReviewState {

    const APP_CIYUANSHE = 1;
    const APP_UNKNOWN_MESSAGE = 2;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO");
     * @ORM\Column(name="id", type="integer")
     */
    public $id;

    /**
     * @var int
     * @ORM\Column(name="app_id", type="integer")
     */
    public $appId;

    /**
     * @var string
     *
     * @ORM\Column(name="channel", type="string", length=100)
     */
    public $channel;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=100)
     */
    public $version;

    /**
     * @var bool
     *
     * @ORM\Column(name="in_review", type="boolean")
     */
    public $inReview;

}