<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 6/15/16
 * Time: 12:16 PM
 */

namespace Lychee\Bundle\AdminBundle\Service;


use Lychee\Bundle\AdminBundle\Entity\GrayList;
use Lychee\Module\Post\PostService;

class GrayListService {

    const TYPE_USER = 1;

    const TYPE_TOPIC = 2;

    const SORT_BY_DELETE_COUNT = 1;

    const SORT_BY_DELETE_TIME = 2;
    
    private $doctrine;

    private $postService;

    public function __construct($doctrine, PostService $postService) {
        $this->doctrine = $doctrine;
        $this->postService = $postService;
    }

    public function log($postId, $managerId) {
        $post = $this->postService->fetchOne($postId);
        if ($post) {
            $now = new \DateTime();
            /** @var \PDO $conn */
            $conn = $this->doctrine->getConnection();
            $stmt = $conn->prepare(
                "INSERT INTO admin_gray_list VALUE(:postId, :creatorId, :topicId, :operatingTime, :managerId)
                ON DUPLICATE KEY UPDATE creator_id=:creatorId, topic_id=:topicId, operating_time=:operatingTime, manager_id=:managerId"
            );
            $stmt->bindValue(':postId', $postId);
            $stmt->bindValue(':creatorId', $post->authorId);
            $stmt->bindValue(':topicId', $post->topicId);
            $stmt->bindValue(':operatingTime', $now->format('Y-m-d H:i:s'));
            $stmt->bindValue(':managerId', $managerId);
            $stmt->execute();
        }
    }
    
    public function fetch($type, $sortBy, $page, $count = 20) {
        $tb = GrayList::TABLE_NAME;
        $offset = ($page - 1) * $count;
        if ($type == self::TYPE_USER) {
            $selector = 'creator_id';
        } else {
            $selector = 'topic_id';
        }
        if ($sortBy == self::SORT_BY_DELETE_COUNT) {
            $orderBy = 'counter';
        } else {
            $orderBy = 'op_time';
        }
        $sql = "SELECT {$selector}, MAX(operating_time) op_time, COUNT(post_id) counter FROM {$tb}
                GROUP BY {$selector}
                ORDER BY {$orderBy} DESC
                LIMIT {$offset}, {$count}";

        /** @var \PDO $conn */
        $conn = $this->doctrine->getConnection();
        $stmt = $conn->prepare($sql);
        $result = [];
        if ($stmt->execute()) {
            $result = $stmt->fetchAll(\PDO::FETCH_NUM);
        }

        return $result;
    }
    
    public function fetchListCount() {
        $tb = GrayList::TABLE_NAME;
        $sql = "SELECT COUNT(post_id) counter FROM {$tb}";
        /** @var \PDO $conn */
        $conn = $this->doctrine->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_NUM);
        $counter = $result[0];

        return $counter;
    }
}