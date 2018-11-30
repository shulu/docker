<?php
namespace Lychee\Module\Comment\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="robot_comment", uniqueConstraints={
 * }, indexes={
 * })
 */
class RobotComment {

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"unsigned":true, "comment":"评论id"})
     */
    public $id;

}