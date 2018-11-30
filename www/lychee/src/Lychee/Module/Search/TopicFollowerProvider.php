<?php
namespace Lychee\Module\Search;

use Lychee\Component\Foundation\CursorableIterator\CursorableIterator;
use Lychee\Component\Foundation\CursorableIterator\CustomizedCursorableIterator;
use Lychee\Module\Topic\Entity\TopicUserFollowing;

class TopicFollowerProvider extends AbstractProvider {

    protected function getClassName() {
        return TopicUserFollowing::class;
    }

    /**
     * @return CursorableIterator
     */
    protected function getCursorableIterator() {
        return new CustomizedCursorableIterator(function($cursor, $count, &$nextCursor){
            if ($count <= 0) {
                $nextCursor = $cursor;
                return array();
            }
            @list($userId, $topicId) = explode(',', $cursor);
            $userId = intval($userId);
            $topicId = intval($topicId);

            $query = $this->em->createQuery('SELECT a FROM '.TopicUserFollowing::class
                .' a WHERE (a.userId = :user AND a.topicId > :topic) OR a.userId > :user'
                .' ORDER BY a.userId ASC, a.topicId ASC');
            $query->setParameters(array('user' => $userId, 'topic' => $topicId));
            $query->setMaxResults($count);
            $result = $query->getResult();

            if (count($result) < $count) {
                $nextCursor = 0;
            } else {
                /** @var TopicUserFollowing $last */
                $last = $result[count($result) - 1];
                $nextCursor = $last->userId . ',' . $last->topicId;
            }

            return $result;
        });
    }

}