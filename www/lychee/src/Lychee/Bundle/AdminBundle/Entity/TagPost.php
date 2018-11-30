<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 10/16/15
 * Time: 11:51 AM
 */

namespace Lychee\Bundle\AdminBundle\Entity;


use Doctrine\ORM\Mapping as ORM;

/**
 * Class TagPost
 * @package Lychee\Bundle\AdminBundle\Entity
 * @ORM\Entity()
 * @ORM\Table(name="admin_tag_post")
 */
class TagPost {

    /**
     * @var
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(name="id", type="bigint")
     */
    private $id;

    /**
     * @var
     *
     * @ORM\Column(name="tag_id", type="integer")
     */
    private $tagId;

    /**
     * @var
     *
     * @ORM\Column(name="post_id", type="bigint")
     */
    private $postId;

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
    public function getTagId() {
        return $this->tagId;
    }

    /**
     * @param mixed $tagId
     */
    public function setTagId($tagId) {
        $this->tagId = $tagId;
    }

    /**
     * @return mixed
     */
    public function getPostId() {
        return $this->postId;
    }

    /**
     * @param mixed $postId
     */
    public function setPostId($postId) {
        $this->postId = $postId;
    }
}