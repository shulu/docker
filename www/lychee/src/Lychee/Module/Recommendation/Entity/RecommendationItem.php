<?php
namespace Lychee\Module\Recommendation\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="recommendation_item")
 * @ORM\HasLifecycleCallbacks()
 */
class RecommendationItem {

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(name="id", type="bigint")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=20)
     */
    private $type;

    /**
     * @var int
     *
     * @ORM\Column(name="target_id", type="bigint")
     */
    private $targetId;

    /**
     * @var string
     *
     * @ORM\Column(name="reason", type="string", length=200, nullable=true)
     */
    private $reason;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=2083, nullable=true)
     */
    private $image;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_time", type="datetime")
     */
    private $createTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="sticky", type="smallint", options={"default":"0"})
     */
    private $sticky = 0;

	/**
	 * @var int
	 *
	 * @ORM\Column(name="position", type="smallint", options={"default": "0"})
	 */
	private $position = 0;

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return RecommendationItem
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    /**
     * @return int
     */
    public function getTargetId() {
        return $this->targetId;
    }

    /**
     * @param int $targetId
     *
     * @return RecommendationItem
     */
    public function setTargetId($targetId) {
        $this->targetId = $targetId;
        return $this;
    }

    /**
     * @return string
     */
    public function getReason() {
        return $this->reason;
    }

    /**
     * @param string $reason
     *
     * @return RecommendationItem
     */
    public function setReason($reason) {
        $this->reason = $reason;
        return $this;
    }

    /**
     * @return string
     */
    public function getImage() {
        return $this->image;
    }

    /**
     * @param string $image
     *
     * @return RecommendationItem
     */
    public function setImage($image) {
        $this->image = $image;
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
     */
    public function setCreateTime($createTime) {
        $this->createTime = $createTime;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreateTimeValue() {
        $this->createTime = new \DateTime();
    }

    /**
     * @return int
     */
    public function getSticky() {
        return $this->sticky;
    }

    /**
     * @param int $sticky
     */
    public function setSticky($sticky) {
        $this->sticky = $sticky;
    }

	/**
	 * @return int
	 */
	public function getPosition() {
		return $this->position;
	}

	/**
	 * @param int $position
	 */
	public function setPosition( $position ) {
		$this->position = $position;
	}

} 