<?php
namespace Lychee\Module\Recommendation\Post;

use Lychee\Bundle\CoreBundle\Entity\Post;

class VideoGroupResolver extends AbstractGroupResolver {
    public function resolve($postId, PostInfoResolver $pir) {
        $post = $pir->getPost($postId);
        if ($post && $post->type == Post::TYPE_VIDEO && !empty($post->videoUrl)) {
            return $this->groupIds;
        } else {
            return array();
        }
    }

}