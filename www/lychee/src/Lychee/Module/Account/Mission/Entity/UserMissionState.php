<?php
namespace Lychee\Module\Account\Mission\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="user_mission_state")
 */
class UserMissionState {
    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\Column(name="user_id", type="bigint")
     */
    public $userId;

    //新手任务
    /**
     * @var int
     *
     * @ORM\Column(name="filled_profile", type="smallint")
     */
    public $filledProfile = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="invited_friends", type="smallint")
     */
    public $invitedFriends = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="followed_topic", type="smallint")
     */
    public $followedTopic = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="set_favorite_topic", type="smallint")
     */
    public $setFavoriteTopic = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="set_attributes", type="smallint")
     */
    public $setAttributes = 0;

    //每日任务

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="daily_date", type="date", nullable=true)
     */
    public $dailyDate = null;

    /**
     * @var int
     *
     * @ORM\Column(name="daily_like_post", type="smallint")
     */
    public $dailyLikePost = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="daily_comment", type="smallint")
     */
    public $dailyComment = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="daily_img_comment", type="smallint")
     */
    public $dailyImageComment = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="daily_share", type="smallint")
     */
    public $dailyShare = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="daily_post", type="smallint")
     */
    public $dailyPost = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="daily_signin", type="smallint")
     */
    public $dailySignin = 0;

    /**
     * @var bool
     *
     * @ORM\Column(name="activated", type="boolean")
     */
    public $activated = false;
}