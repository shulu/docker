<?php
namespace Lychee\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="post", indexes={
 *   @ORM\Index(name="create_time_index", columns={"create_time"})
 * })
 */
class Post {

    /**
     * 帖子类型:普通
     */
    const TYPE_NORMAL = 0;
    /**
     * 帖子类型:资源
     */
    const TYPE_RESOURCE = 1;
    /**
     * 帖子类型:聊天
     */
    const TYPE_GROUP_CHAT = 2;
    /**
     * 帖子类型:行程表
     */
    const TYPE_SCHEDULE = 3;
    /**
     * 帖子类型:投票
     */
    const TYPE_VOTING = 4;
    /**
     * 帖子类型:视频
     */
    const TYPE_VIDEO = 5;
    /**
     * 帖子类型:直播
     */
    const TYPE_LIVE = 6;
    /**
     * 帖子类型:短视频
     */
    const TYPE_SHORT_VIDEO = 7;

    const STICKY_LEVEL_BY_MANAGER = 1;
    const STICKY_LEVEL_BY_SUPER = 2;

    public static function getTypeEnums(){
        return [
            self::TYPE_NORMAL => '普通',
            self::TYPE_RESOURCE => '资源',
            self::TYPE_GROUP_CHAT => '聊天',
            self::TYPE_SCHEDULE => '行程表',
            self::TYPE_VOTING => '投票',
            self::TYPE_VIDEO => '视频',
            self::TYPE_LIVE => '直播',
            self::TYPE_SHORT_VIDEO => '短视频',
        ];
    }

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     */
    public $id;

    /**
     * @var int
     *
     * @ORM\Column(name="author_id", type="bigint")
     */
    public $authorId;

    /**
     * @var int
     *
     * @ORM\Column(name="reposted_id", type="bigint", nullable=true)
     */
    public $repostedId;

    /**
     * @var int
     *
     * @ORM\Column(name="topic_id", type="bigint", nullable=true)
     */
    public $topicId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_time", type="datetime")
     */
    public $createTime;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=100, nullable=true)
     */
    public $title;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="string", length=2000)
     */
    public $content;

    /**
     * @var string
     *
     * @ORM\Column(name="image_url", type="string", length=2083, nullable=true)
     */
    public $imageUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="video_url", type="string", length=2083, nullable=true)
     */
    public $videoUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="audio_url", type="string", length=2083, nullable=true)
     */
    public $audioUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="site_url", type="string", length=2083, nullable=true)
     */
    public $siteUrl;

    /**
     * @var float
     *
     * @ORM\Column(name="longitude", type="float", nullable=true)
     */
    public $longitude;

    /**
     * @var float
     *
     * @ORM\Column(name="latitude", type="float", nullable=true)
     */
    public $latitude;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=200, nullable=true)
     */
    public $address;

    /**
     * @var bool
     *
     * @ORM\Column(name="deleted", type="boolean")
     */
    public $deleted = false;

    /**
     * @var string
     *
     * @ORM\Column(name="news_source", type="string", length=200, nullable=true)
     */
    public $newsSource;

    /**
     * @var string
     *
     * @ORM\Column(name="annotation", type="string", length=2048, nullable=true)
     */
    public $annotation;

    /**
     * @var bool
     *
     * @ORM\Column(name="folded", type="boolean")
     */
    public $folded = false;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="smallint", options={"default":0})
     */
    public $type;

    /**
     * @var int
     *
     * @ORM\Column(name="sticky_level", type="smallint", options={"default":0})
     */
    public $stickyLevel = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="im_group_id", type="bigint", nullable=true)
     */
    public $imGroupId;

    /**
     * @var int
     *
     * @ORM\Column(name="schedule_id", type="bigint", nullable=true)
     */
    public $scheduleId;

    /**
     * @var int
     *
     * @ORM\Column(name="voting_id", type="bigint", nullable=true)
     */
    public $votingId;
}