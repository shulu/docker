<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 10/16/15
 * Time: 11:54 AM
 */

namespace Lychee\Bundle\AdminBundle\Service;


use Doctrine\Common\Persistence\ManagerRegistry;
use Lychee\Bundle\AdminBundle\Entity\Tag;
use Lychee\Bundle\AdminBundle\Entity\TagPost;

/**
 * Class TagService
 * @package Lychee\Bundle\AdminBundle\Service
 */
class TagService {

    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    private $entityManager;

    /**
     * @param ManagerRegistry $registry
     * @param null $entityManagerName
     */
    public function __construct(ManagerRegistry $registry, $entityManagerName = null) {
        $this->entityManager = $registry->getManager($entityManagerName);
    }

    /**
     * @return mixed
     */
    public function fetchAllTags() {
        $tagRepo = $this->entityManager->getRepository(Tag::class);

        return $tagRepo->findBy([], [
            'createTime' => 'DESC'
        ]);
    }

    /**
     * @param $tagName
     * @param $creatorId
     * @return bool
     */
    public function createTag($tagName, $creatorId) {
        $existTag = $this->entityManager->getRepository(Tag::class)->findOneBy([
            'name' => $tagName
        ]);
        if ($existTag) {
            return false;
        }
        $tag = new Tag();
        $tag->setName($tagName);
        $tag->setCreatorId($creatorId);

        $this->entityManager->persist($tag);
        $this->entityManager->flush();
    }

    /**
     * @param Tag $tag
     */
    public function removeTag(Tag $tag) {
        $query = $this->entityManager->createQuery(
            'DELETE Lychee\Bundle\AdminBundle\Entity\TagPost tp
            WHERE tp.tagId = :tagId'
        )->setParameter('tagId', $tag->getId());
        $query->getResult();
        $this->entityManager->remove($tag);
        $this->entityManager->flush();
    }

    /**
     * @param $id
     * @return object
     */
    public function fetchOne($id) {
        return $this->entityManager->getRepository(Tag::class)->find($id);
    }

    /**
     * @param $postId
     * @param $tagId
     */
    public function addPostToTag($postId, $tagId) {
        /**
         * @var \Lychee\Bundle\AdminBundle\Entity\Tag $tag
         */
        $tag = $this->fetchOne($tagId);
        if ($tag) {
            $tag->setPostCount($tag->getPostCount() + 1);
            $tagPost = new TagPost();
            $tagPost->setTagId($tagId);
            $tagPost->setPostId($postId);
            $this->entityManager->persist($tagPost);
            $this->entityManager->flush();
        }
    }

    /**
     * @param $postId
     */
    public function removeTagPostByPostId($postId) {
        $result = $this->entityManager->getRepository(TagPost::class)->findBy([
            'postId' => $postId,
        ]);
        foreach ($result as $row) {
            $tag = $this->fetchOne($row->getTagId());
            $tag->setPostCount($tag->getPostCount() - 1);
            $this->entityManager->remove($row);
        }
        $this->entityManager->flush();
    }

    /**
     * @param $tagId
     * @param $tagName
     */
    public function updateTag($tagId, $tagName) {
        $tag = $this->fetchOne($tagId);
        if ($tag) {
            $tag->setName($tagName);
            $this->entityManager->flush();
        }
    }

    /**
     * @param $tagId
     * @param int $count
     * @param int $page
     * @return array
     */
    public function fetchIdsByTagId($tagId, $page = 1, $count = 20) {
        $result = $this->entityManager->getRepository(TagPost::class)->findBy([
            'tagId' => $tagId
        ], [
            'id' => 'DESC'
        ], $count, ($page - 1) * $count);

        return array_map(function($tagPost) {
            return $tagPost->getPostId();
        }, $result);
    }
}