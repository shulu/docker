<?php
namespace Lychee\Module\Like\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="robot_like_post_task", uniqueConstraints={
 * }, indexes={
 *   @ORM\Index(name="state_create_time_idx", columns={"state", "create_time"})
 * })
 */
class RobotLikePostTask {

    const WAITING_STATE = 1;
    const PROCESSING_STATE = 2;
    const FINISHED_STATE = 3;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(name="id", type="bigint", options={"unsigned":true})
     */
    public $id;

    /**
     * @var int
     *
     * @ORM\Column(name="total", type="bigint", options={"unsigned":true})
     */
    public $total;

    /**
     * @var int
     *
     * @ORM\Column(name="post_id", type="bigint", options={"unsigned":true})
     */
    public $postId;

    /**
     * @var int
     *
     * @ORM\Column(name="state", type="smallint", options={"comment":"任务当前状态, 1：未处理，2：处理中，3：已处理"})
     */
    public $state = self::WAITING_STATE;

    /**
     * @var int
     *
     * @ORM\Column(name="update_time", type="integer", options={"comment":"任务状态更新时间"})
     */
    public $updateTime;

    /**
     * @var int
     *
     * @ORM\Column(name="create_time", type="integer", options={"comment":"任务创建时间"})
     */
    public $createTime;
}