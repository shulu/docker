<?php
namespace Lychee\Module\Search;

use Elastica\Type;
use Elastica\Document;
use Lychee\Module\Account\AccountService;
use Lychee\Module\Topic\Entity\TopicUserFollowing;

class TopicFollowerIndexer extends AbstractIndexer {

    private $accountService;

    /**
     * FollowingIndexer constructor.
     *
     * @param Type $type
     * @param AccountService $accountService
     */
    public function __construct(Type $type, $accountService) {
        parent::__construct($type);
        $this->accountService = $accountService;
    }

    /**
     * @param TopicUserFollowing $object
     *
     * @return Document
     */
    protected function toDocument($object) {
        if ($object->state != TopicUserFollowing::STATE_DELETED) {
            $user = $this->accountService->fetchOne($object->userId);
            if ($user->nickname == null) {
                return null;
            }

            $data = array(
                'topic_id' => intval($object->topicId),
                'user_id' => intval($object->userId),
                'nickname' => $user->nickname
            );
            return new Document($this->getId($object), $data);
        } else {
            return null;
        }
    }

    /**
     * @param TopicUserFollowing $object
     *
     * @return mixed
     */
    protected function getId($object) {
        return $object->topicId . '-' . $object->userId;
    }
}