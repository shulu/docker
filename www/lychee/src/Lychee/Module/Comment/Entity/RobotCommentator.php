<?php
namespace Lychee\Module\Comment\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="robot_commentator", uniqueConstraints={
 * }, indexes={
 *   @ORM\Index(name="action_time_total_idx", columns={"action_time", "total"})
 * })
 */
class RobotCommentator {

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"unsigned":true, "comment":"点赞用户id"})
     */
    public $id;

    /**
     * @var int
     *
     * @ORM\Column(name="total", type="bigint", options={"unsigned":true, "comment":"点赞次数"})
     */
    public $total;

    /**
     * @var int
     *
     * @ORM\Column(name="action_time", type="integer", options={"comment":"最后一次操作时间"})
     */
    public $actionTime;

}