<?php
namespace Lychee\Module\Recommendation\Post;

class TopicCategoryGroup extends AbstractGroup {

    private $categoryId;

    public function __construct($id, $name, $categoryId) {
        parent::__construct($id, $name);
        $this->categoryId = $categoryId;
    }

    public function resolver() {
        return new TopicCategoryGroupResolver($this->id(), $this->categoryId);
    }

}