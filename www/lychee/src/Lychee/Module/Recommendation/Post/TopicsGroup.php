<?php
namespace Lychee\Module\Recommendation\Post;

class TopicsGroup extends AbstractGroup {

    private $topicIds;

    public function __construct($id, $name, $topicIds) {
        parent::__construct($id, $name);
        $this->topicIds = $topicIds;
    }

    public function resolver() {
        return new TopicsGroupResolver($this->id(), $this->topicIds);
    }

    public function getTopicIds() {
        return $this->topicIds;
    }

}