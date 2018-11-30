<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 3/20/15
 * Time: 3:44 PM
 */

namespace Lychee\Module\Recommendation\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class SpecialSubjectRelation
 * @package Lychee\Module\Recommendation\Entity
 * @ORM\Entity()
 * @ORM\Table(name="special_subject_relation", indexes={
 *   @ORM\Index(name="special_subject_index", columns={"special_subject_id"})
 * })
 */
class SpecialSubjectRelation {

    const TYPE_POST = 'post';
    const TYPE_TOPIC = 'topic';
    const TYPE_USER = 'user';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(name="id", type="bigint")
     */
    private $id;

    /**
     * @var
     *
     * @ORM\Column(name="type", type="string")
     */
    private $type;

    /**
     * @var
     *
     * @ORM\ManyToOne(targetEntity="SpecialSubject", inversedBy="relations")
     * @ORM\JoinColumn(name="special_subject_id", referencedColumnName="id")
     */
    private $specialSubject;

    /**
     * @var
     *
     * @ORM\Column(name="associated_id", type="bigint")
     */
    private $associatedId;

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
     * @return mixed
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getSpecialSubject() {
        return $this->specialSubject;
    }

    /**
     * @param mixed $specialSubject
     */
    public function setSpecialSubject($specialSubject) {
        $this->specialSubject = $specialSubject;
    }

    /**
     * @return mixed
     */
    public function getAssociatedId() {
        return $this->associatedId;
    }

    /**
     * @param mixed $associatedId
     */
    public function setAssociatedId($associatedId) {
        $this->associatedId = $associatedId;
    }
}