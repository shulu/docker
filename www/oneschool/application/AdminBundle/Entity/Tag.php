<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 10/16/15
 * Time: 11:36 AM
 */

namespace Lychee\Bundle\AdminBundle\Entity;


use Doctrine\ORM\Mapping as ORM;

/**
 * Class Tag
 * @package Lychee\Bundle\AdminBundle\Entity
 * @ORM\Entity()
 * @ORM\Table(name="admin_tag")
 * @ORM\HasLifecycleCallbacks()
 */
class Tag {

    /**
     * @var
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    private $id;

    /**
     * @var
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    private $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_time", type="datetime")
     */
    private $createTime;

    /**
     * @var string
     *
     * @ORM\Column(name="creator_id", type="integer")
     */
    private $creatorId;

    /**
     * @var int
     *
     * @ORM\Column(name="post_count", type="integer")
     */
    private $postCount = 0;

    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return \DateTime
     */
    public function getCreateTime() {
        return $this->createTime;
    }

    /**
     * @param \DateTime $createTime
     */
    public function setCreateTime($createTime) {
        $this->createTime = $createTime;
    }

    /**
     * @return string
     */
    public function getCreatorId() {
        return $this->creatorId;
    }

    /**
     * @param string $creatorId
     */
    public function setCreatorId($creatorId) {
        $this->creatorId = $creatorId;
    }

    /**
     * @return int
     */
    public function getPostCount() {
        return $this->postCount;
    }

    /**
     * @param int $postCount
     */
    public function setPostCount($postCount) {
        $this->postCount = $postCount;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreateTimeValue() {
        $this->createTime = new \DateTime();
    }

}