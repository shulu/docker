<?php
namespace Lychee\Module\Robot\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="robot", uniqueConstraints={
 * }, indexes={
 * })
 */
class Robot {

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"unsigned":true, "comment":"用户id"})
     */
    public $id;

}