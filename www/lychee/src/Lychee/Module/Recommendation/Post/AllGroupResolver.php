<?php
namespace Lychee\Module\Recommendation\Post;

class AllGroupResolver extends AbstractGroupResolver {

    protected $excludeTopicIds=[];

    /**
     * 排除指定的次元
     *
     * @param $topicIds
     */
    public function excludeTopics($topicIds) {
        $this->excludeTopicIds = array_merge($this->excludeTopicIds, $topicIds);
    }

    public function getExcludeTopicIds() {
        return $this->excludeTopicIds;
    }

    public function resolve($postId, PostInfoResolver $pir) {
        if (empty($this->excludeTopicIds)) {
            return $this->groupIds;
        }

        $post = $pir->getPost($postId);
        if (empty($post)) {
            return [];
        }

        if (in_array($post->topicId, $this->excludeTopicIds)) {
            return [];
        }

        return $this->groupIds;
    }

}