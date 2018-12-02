<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 8/17/15
 * Time: 4:40 PM
 */

namespace Lychee\Module\Recommendation\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class ColumnElement
 * @package Lychee\Module\Recommendation\Entity
 * @ORM\Entity()
 * @ORM\Table(name="column_element")
 * @ORM\HasLifecycleCallbacks()
 */
class ColumnElement {

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="column_id", type="integer")
     */
    private $columnId;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="element_id", type="bigint")
     */
    private $elementId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_time", type="datetime")
     */
    private $createTime;

    /**
     * @var String
     *
     * @ORM\Column(name="recommendation_reason", type="string", length=255, nullable=true, options={"collation":"utf8mb4_unicode_ci"})
     */
    private $recommendationReason;

    /**
     * @var int
     *
     * @ORM\Column(name="`order`", type="integer")
     */
    private $order;

    /**
     * @var string|null
     * @ORM\Column(name="image_url", type="string", length=255, nullable=true)
     */
    private $imageUrl = null;

    /**
     * @return int
     */
    public function getColumnId() {
        return $this->columnId;
    }

    /**
     * @param int $columnId
     */
    public function setColumnId($columnId) {
        $this->columnId = $columnId;
    }

    /**
     * @return int
     */
    public function getElementId() {
        return $this->elementId;
    }

    /**
     * @param int $elementId
     */
    public function setElementId($elementId) {
        $this->elementId = $elementId;
    }

    /**
     * @return mixed
     */
    public function getCreateTime() {
        return $this->createTime;
    }

    /**
     * @param mixed $createTime
     */
    public function setCreateTime($createTime) {
        $this->createTime = $createTime;
    }

    /**
     * @return String
     */
    public function getRecommendationReason() {
        return $this->recommendationReason;
    }

    /**
     * @param String $recommendationReason
     */
    public function setRecommendationReason($recommendationReason) {
        $this->recommendationReason = $recommendationReason;
    }

    /**
     * @return int
     */
    public function getOrder() {
        return $this->order;
    }

    /**
     * @param int $order
     */
    public function setOrder($order) {
        $this->order = $order;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreateTimeValue() {
        $this->createTime = new \DateTime();
    }

    /**
     * @return string
     */
    public function getImageUrl() {
        return $this->imageUrl;
    }

    /**
     * @param string $imageUrl
     */
    public function setImageUrl($imageUrl) {
        $this->imageUrl = $imageUrl;
    }

}