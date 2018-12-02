<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 6/9/15
 * Time: 5:29 PM
 */

namespace Lychee\Module\Recommendation\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Entity()
 * @ORM\Table(name="admin_recommendation_cron_job")
 * @ORM\HasLifecycleCallbacks()
 */
class RecommendationCronJob {

    /**
     * @var
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var
     *
     * @ORM\Column(name="recommendation_type", type="string", length=255)
     */
    private $recommendationType;

    /**
     * @var
     *
     * @ORM\Column(name="recommendation_id", type="bigint")
     */
    private $recommendationId;

    /**
     * @var
     *
     * @ORM\Column(name="create_time", type="datetime")
     */
    private $createTime;

    /**
     * @var
     *
     * @ORM\Column(name="publish_time", type="datetime")
     */
    private $publishTime;

    /**
     * @var
     *
     * @ORM\Column(name="recommended_reason", type="string", length=255, nullable=true)
     */
    private $recommendedReason;

    /**
     * @var
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $image;

    /**
     * @var
     * 
     * @ORM\Column(type="string", length=2047, nullable=true)
     */
    private $annotation;

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
    public function getRecommendationType() {
        return $this->recommendationType;
    }

    /**
     * @param mixed $recommendationType
     */
    public function setRecommendationType($recommendationType) {
        $this->recommendationType = $recommendationType;
    }

    /**
     * @return mixed
     */
    public function getRecommendationId() {
        return $this->recommendationId;
    }

    /**
     * @param mixed $recommendationId
     */
    public function setRecommendationId($recommendationId) {
        $this->recommendationId = $recommendationId;
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
     * @return mixed
     */
    public function getPublishTime() {
        return $this->publishTime;
    }

    /**
     * @param mixed $publishTime
     */
    public function setPublishTime($publishTime) {
        $this->publishTime = $publishTime;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreateTimeValue() {
        $this->createTime = new \DateTime();
    }

    /**
     * @return mixed
     */
    public function getRecommendedReason() {
        return $this->recommendedReason;
    }

    /**
     * @param mixed $recommendedReason
     */
    public function setRecommendedReason($recommendedReason) {
        $this->recommendedReason = $recommendedReason;
    }

    /**
     * @return mixed
     */
    public function getImage() {
        return $this->image;
    }

    /**
     * @param mixed $image
     */
    public function setImage($image) {
        $this->image = $image;
    }

    /**
     * @return mixed
     */
    public function getAnnotation()
    {
        return $this->annotation;
    }

    /**
     * @param mixed $annotation
     */
    public function setAnnotation($annotation)
    {
        $this->annotation = $annotation;
    }
}