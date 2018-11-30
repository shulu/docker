<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 10/28/15
 * Time: 11:55 AM
 */

namespace Lychee\Module\ContentManagement\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Expression
 * @package Lychee\Module\ContentManagement\Entity
 * @ORM\Entity()
 * @ORM\Table(name="expression")
 */
class Expression {

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
     * @ORM\Column(name="package_id", type="integer")
     */
    private $packageId;

    /**
     * @var
     *
     * @ORM\Column(name="image_url", type="string", length=255)
     */
    private $imageUrl;

    /**
     * @var
     *
     * @ORM\Column(type="string", length=255))
     */
    private $name;

    /**
     * @var
     *
     * @ORM\Column(type="string", length=255)
     */
    private $filename;

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
    public function getPackageId() {
        return $this->packageId;
    }

    /**
     * @param mixed $packageId
     */
    public function setPackageId($packageId) {
        $this->packageId = $packageId;
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
    public function getFilename() {
        return $this->filename;
    }

    /**
     * @param mixed $filename
     */
    public function setFilename($filename) {
        $this->filename = $filename;
    }

    /**
     * @return mixed
     */
    public function getImageUrl() {
        return $this->imageUrl;
    }

    /**
     * @param mixed $imageUrl
     */
    public function setImageUrl($imageUrl) {
        $this->imageUrl = $imageUrl;
    }

}