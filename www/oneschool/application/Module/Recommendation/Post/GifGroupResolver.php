<?php
namespace Lychee\Module\Recommendation\Post;

class GifGroupResolver extends AbstractGroupResolver {

    public function resolve($postId, PostInfoResolver $pir) {
        $annotation = $pir->getPostAnnotation($postId);
        if (isset($annotation['multi_photo_gif_index']) && !empty($annotation['multi_photo_gif_index'])) {
            return $this->groupIds;
        } else {
            return array();
        }
    }

}