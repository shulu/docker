<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 10/28/15
 * Time: 11:35 AM
 */

namespace Lychee\Module\ContentManagement\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class ExpressionPackage
 * @package Lychee\Module\ContentManagement\Entity
 * @ORM\Entity()
 * @ORM\Table(name="expression_package")
 * @ORM\HasLifecycleCallbacks()
 */
class ExpressionPackage {

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
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @var
     *
     * @ORM\Column(name="cover_image", type="string", length=255)
     */
    private $coverImage;

    /**
     * @var
     *
     * @ORM\Column(name="create_time", type="datetime")
     */
    private $createTime;

    /**
     * @var
     *
     * @ORM\Column(name="last_modified_time", type="datetime")
     */
    private $lastModifiedTime;

    /**
     * @var
     *
     * @ORM\Column(type="boolean")
     */
    private $deleted = false;

    /**
     * @var
     *
     * @ORM\Column(type="string", length=255)
     */
    private $downloadUrl;

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
     * @return mixed
     */
    public function getCoverImage() {
        return $this->coverImage;
    }

    /**
     * @param mixed $coverImage
     */
    public function setCoverImage($coverImage) {
        $this->coverImage = $coverImage;
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
    public function getLastModifiedTime() {
        return $this->lastModifiedTime;
    }

    /**
     * @param mixed $lastModifiedTime
     */
    public function setLastModifiedTime($lastModifiedTime) {
        $this->lastModifiedTime = $lastModifiedTime;
    }

    /**
     * @return mixed
     */
    public function getDeleted() {
        return $this->deleted;
    }

    /**
     * @param mixed $deleted
     */
    public function setDeleted($deleted) {
        $this->deleted = $deleted;
    }

    /**
     * @return mixed
     */
    public function getDownloadUrl() {
        return $this->downloadUrl;
    }

    /**
     * @param mixed $downloadUrl
     */
    public function setDownloadUrl($downloadUrl) {
        $this->downloadUrl = $downloadUrl;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreateTimeValue() {
        $now = new \DateTime();
        $this->createTime = $now;
        $this->lastModifiedTime = $now;
        $this->deleted = false;
    }

    /**
     * @ORM\PreUpdate
     */
    public function updateLastModifiedTimeValue() {
        $this->lastModifiedTime = new \DateTime();
    }
}