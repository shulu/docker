<?php
namespace Lychee\Module\Recommendation\Post;

class TopicCategoryGroupResolver extends AbstractGroupResolver {

    private $categoryId;

    public function __construct($groupIds, $categoryId) {
        parent::__construct($groupIds);
        $this->categoryId = $categoryId;
    }

    public function resolve($postId, PostInfoResolver $pir) {
        $categoryId = $pir->getTopicCategoryId($postId);
        if ($categoryId && $this->categoryId == $categoryId) {
            return $this->groupIds;
        } else {
            return array();
        }
    }

}