<?php


namespace Lychee\Module\Recommendation\Post;


interface GroupResolver {
    /**
     * @param int $postId
     * @param PostInfoResolver $pir
     * @return int[]
     */
    public function resolve($postId, PostInfoResolver $pir);
}