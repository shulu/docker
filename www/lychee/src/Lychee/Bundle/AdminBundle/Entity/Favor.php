<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 5/13/15
 * Time: 3:02 PM
 */

namespace Lychee\Bundle\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 *
 * @ORM\Entity()
 * @ORM\Table(name="admin_favor")
 * @ORM\HasLifecycleCallbacks()
 */
class Favor {

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="post_id", type="bigint")
     */
    public $postId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_time", type="datetime")
     */
    private $createTime;

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getPostId() {
        return $this->postId;
    }

    /**
     * @param int $postId
     * @return $this
     */
    public function setPostId($postId) {
        $this->postId = $postId;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreateTime() {
        return $this->createTime;
    }

    /**
     * @param \DateTime $createTime
     * @return $this
     */
    public function setCreateTime($createTime) {
        $this->createTime = $createTime;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreateTimeValue() {
        $this->createTime = new \DateTime();
    }

}