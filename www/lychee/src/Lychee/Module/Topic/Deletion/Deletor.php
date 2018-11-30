<?php

namespace Lychee\Module\Topic\Deletion;


interface Deletor {
    /**
     * @param int $topicId
     *
     */
    public function delete($topicId);
}