<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 1/20/16
 * Time: 3:18 PM
 */

namespace Lychee\Module\ContentAudit\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class AuditImage
 * @package Lychee\Module\ContentAudit\Entity
 * @ORM\Entity()
 * @ORM\Table(name="audit_image", uniqueConstraints={@ORM\UniqueConstraint(name="image_post_idx", columns={"image_url","post_id"})})
 */
class AuditImage {

    const IMAGE_TYPE_PORN = 1;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(type="bigint")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="image_url", type="string", length=255)
     */
    private $imageUrl;

    /**
     * @var integer
     *
     * @ORM\Column(name="post_id", type="bigint")
     */
    private $postId;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="smallint")
     */
    private $type;

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

    /**
     * @return int
     */
    public function getPostId() {
        return $this->postId;
    }

    /**
     * @param int $postId
     */
    public function setPostId($postId) {
        $this->postId = $postId;
    }

    /**
     * @return int
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType($type) {
        $this->type = $type;
    }

}