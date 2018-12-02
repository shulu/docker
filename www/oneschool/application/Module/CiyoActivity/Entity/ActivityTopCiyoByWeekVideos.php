<?php
namespace Lychee\Module\CiyoActivity\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="activity_top_ciyo_by_week_videos", indexes={
 *   @ORM\Index(name="activity_top_ciyo_by_week_id", columns={"activity_top_ciyo_by_week_id"}),
 *   @ORM\Index(name="video_id", columns={"video_id"})
 * },options={"comment":"每周精选次元视频属性"})
 */
class ActivityTopCiyoByWeekVideos {
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(name="id", type="bigint")
     */
    public $id;
    /**
     * @var int
     *
     * @ORM\Column(name="activity_top_ciyo_by_week_id", type="bigint",options={"comment":"每周精选次元ID"}))
     */
    public $activity_top_ciyo_by_week_id;
    /**
     * @var int
     *
     * @ORM\Column(name="video_id", type="bigint",options={"comment":"视频ID"}))
     */
    public $video_id;
    /**
     * @var string
     *
     * @ORM\Column(name="reason_text", type="string" , length=255,options={"comment":"上榜理由"}))
     */
    public $reason_text;
}