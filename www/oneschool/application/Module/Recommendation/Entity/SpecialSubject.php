<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 3/20/15
 * Time: 3:38 PM
 */

namespace Lychee\Module\Recommendation\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class SpecialSubject
 * @package Lychee\Module\Recommendation\Entity
 * @ORM\Entity()
 * @ORM\Table(name="special_subject")
 * @ORM\HasLifecycleCallbacks()
 */
class SpecialSubject {

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
     * @ORM\Column(name="create_time", type="datetime")
     */
    private $createTime;

    /**
     * @var
     *
     * @ORM\Column(name="name", type="string", length=50)
     */
    private $name;

    /**
     * @var
     *
     * @ORM\Column(name="banner", type="string", length=2083)
     */
    private $banner;

    /**
     * @var
     *
     * @ORM\Column(name="description", type="string", length=1000)
     */
    private $description;

    /**
     * @var
     *
     * @ORM\OneToMany(targetEntity="SpecialSubjectRelation", mappedBy="specialSubject")
     */
    private $relations;

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
    public function getBanner() {
        return $this->banner;
    }

    /**
     * @param mixed $banner
     */
    public function setBanner($banner) {
        $this->banner = $banner;
    }

    /**
     * @return mixed
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description) {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getRelations() {
        return $this->relations;
    }

    /**
     * @param mixed $relations
     */
    public function setRelations($relations) {
        $this->relations = $relations;
    }

    /**
     * @param SpecialSubjectRelation $relation
     */
    public function addRelation(SpecialSubjectRelation $relation) {
        $this->relations[] = $relation;
    }

    /**
     *
     */
    public function __construct() {
        $this->relations = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedTimeValue() {
        $this->createTime = new \DateTime();
    }
}