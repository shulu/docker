<?php
namespace Lychee\Module\Recommendation\Post;

use Lychee\Bundle\CoreBundle\Entity\Post;

interface PostInfoResolver {

    /**
     * @param int $postId
     * @return int|null
     */
    public function getTopicCategoryId($postId);

    /**
     * @param int $postId
     * @return Post|null
     */
    public function getPost($postId);

    /**
     * @param int $postId
     * @return array|null
     */
    public function getPostAnnotation($postId);

}