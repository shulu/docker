<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 8/10/16
 * Time: 4:57 PM
 */

namespace Lychee\Module\ContentAudit\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class AntiRubbish
 * @package Lychee\Module\ContentAudit\Entity
 * @ORM\Entity()
 * @ORM\Table(name="anti_rubbish")
 * @ORM\HasLifecycleCallbacks()
 */
class AntiRubbish {

    const TYPE_POST = 1;

    const TYPE_COMMENT = 2;

    /**
     * @var integer
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(type="bigint")
     */
    private $id;

    /**
     * @var integer
     * @ORM\Column(type="bigint", name="user_id")
     */
    private $userId;

    /**
     * @var integer
     * @ORM\Column(type="smallint")
     */
    private $type;

    /**
     * @var integer
     * @ORM\Column(type="bigint", name="target_id")
     */
    private $targetId;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", name="create_time")
     */
    private $createTime;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getTargetId()
    {
        return $this->targetId;
    }

    /**
     * @param int $targetId
     */
    public function setTargetId($targetId)
    {
        $this->targetId = $targetId;
    }

    /**
     * @return \DateTime
     */
    public function getCreateTime()
    {
        return $this->createTime;
    }

    /**
     * @param \DateTime $createTime
     */
    public function setCreateTime($createTime)
    {
        $this->createTime = $createTime;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreateTimeValue() {
        $this->createTime = new \DateTime();
    }
}