<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 5/27/15
 * Time: 3:10 PM
 */

namespace Lychee\Module\ContentManagement\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class SearchKeyword
 * @package Lychee\Module\ContentManagement\Entity
 * @ORM\Entity()
 * @ORM\Table(name="search_keyword")
 * @ORM\HasLifecycleCallbacks()
 */
class SearchKeyword {

    /**
     * @var
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(type="bigint")
     */
    private $id;

    /**
     * @var
     *
     * @ORM\Column(type="string", length=255)
     */
    private $keyword;

    /**
     * @var
     *
     * @ORM\Column(name="create_time", type="datetime")
     */
    private $createTime;

    /**
     * @var
     *
     * @ORM\Column(name="last_record_time", type="datetime")
     */
    private $lastRecordTime;

    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return $this
     */
    public function setId($id) {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getKeyword() {
        return $this->keyword;
    }

    /**
     * @param mixed $keyword
     * @return $this
     */
    public function setKeyword($keyword) {
        $this->keyword = $keyword;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreateTime() {
        return $this->createTime;
    }

    /**
     * @param mixed $createTime
     * @return $this
     */
    public function setCreateTime($createTime) {
        $this->createTime = $createTime;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastRecordTime() {
        return $this->lastRecordTime;
    }

    /**
     * @param mixed $lastRecordTime
     * @return $this
     */
    public function setLastRecordTime($lastRecordTime) {
        $this->lastRecordTime = $lastRecordTime;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreateTimeValue() {
        $this->createTime = new \DateTime();
    }
}