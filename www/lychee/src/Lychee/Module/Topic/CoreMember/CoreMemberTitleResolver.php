<?php
namespace Lychee\Module\Topic\CoreMember;

class CoreMemberTitleResolver {

    private $titleMap;

    public function __construct($titleMap) {
        $this->titleMap = $titleMap;
    }

    /**
     * @param int $topicId
     * @param int $userId
     *
     * @return string|null
     */
    public function resolve($topicId, $userId) {
        if (isset($this->titleMap[$topicId]) && isset($this->titleMap[$topicId][$userId])) {
            return $this->titleMap[$topicId][$userId];
        } else {
            return null;
        }
    }

}