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
 * Class SearchRecord
 * @package Lychee\Module\ContentManagement\Entity
 * @ORM\Entity()
 * @ORM\Table(name="search_record")
 * @ORM\HasLifecycleCallbacks()
 */
class SearchRecord {

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
     * @ORM\Column(name="keyword_id", type="bigint")
     */
    private $keywordId;

    /**
     * @var
     *
     * @ORM\Column(name="user_id", type="bigint")
     */
    private $userId;

    /**
     * @var
     *
     * @ORM\Column(name="record_time", type="datetime")
     */
    private $recordTime;

    /**
     * @var
     *
     * @ORM\Column(name="search_type", type="string", length=20)
     */
    private $searchType;

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
    public function getKeywordId() {
        return $this->keywordId;
    }

    /**
     * @param mixed $keywordId
     * @return $this
     */
    public function setKeywordId($keywordId) {
        $this->keywordId = $keywordId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUserId() {
        return $this->userId;
    }

    /**
     * @param mixed $userId
     * @return $this
     */
    public function setUserId($userId) {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRecordTime() {
        return $this->recordTime;
    }

    /**
     * @param mixed $recordTime
     * @return $this
     */
    public function setRecordTime($recordTime) {
        $this->recordTime = $recordTime;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSearchType() {
        return $this->searchType;
    }

    /**
     * @param mixed $searchType
     * @return $this
     */
    public function setSearchType($searchType) {
        $this->searchType = $searchType;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function setRecordTimeValue() {
        $this->recordTime = new \DateTime();
    }
}