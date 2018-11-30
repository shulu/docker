<?php
namespace Lychee\Module\Recommendation\Post;

class NotInTopicsGroupResolver extends AbstractGroupResolver {

    private $topicIds;

    public function __construct($groupIds, $topicIds) {
        parent::__construct($groupIds);
        $this->topicIds = $topicIds;
    }

    public function resolve($postId, PostInfoResolver $pir) {
        $post = $pir->getPost($postId);
        if ($post && $post->topicId && !in_array($post->topicId, $this->topicIds)) {
            return $this->groupIds;
        } else {
            return array();
        }
    }

}