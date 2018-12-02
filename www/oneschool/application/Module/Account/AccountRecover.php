<?php
namespace Lychee\Module\Account;

use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Foundation\CursorableIterator\CustomizedCursorableIterator;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\DBAL\Connection;
use Lychee\Module\Account\AccountService;
use Lychee\Module\Post\PostService;
use Lychee\Module\Comment\CommentService;

class AccountRecover {

    /**
     * @var Connection
     */
    private $connection;
    private $accountService;
    private $postService;
    private $commentService;

    /**
     * AccountRecover constructor.
     *
     * @param RegistryInterface $registry
     * @param AccountService $accoutService
     * @param PostService $postService
     * @param CommentService $commentService
     */
    public function __construct($registry, $accoutService, $postService, $commentService) {
        $this->connection = $registry->getConnection();
        $this->accountService = $accoutService;
        $this->postService = $postService;
        $this->commentService = $commentService;
    }

    public function recover($userId) {
        $this->recoverPost($userId);
        $this->recoverComment($userId);
    }

    private function buildUserPostIterator($userId) {
        return new CustomizedCursorableIterator(function($cursor, $step, &$nextCursor) use($userId) {
            if ($step == 0) {
                $nextCursor = $cursor;
                return array();
            }

            $sql = 'SELECT id FROM post WHERE id > ? AND author_id = ? AND deleted = 1 LIMIT ?';
            $stat = $this->connection->executeQuery($sql, array($cursor, $userId, $step),
                array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
            $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
            if (count($rows) < $step) {
                $nextCursor = 0;
            } else {
                $nextCursor = $rows[count($rows) - 1]['id'];
            }
            return ArrayUtility::columns($rows, 'id');
        });
    }

    public function recoverPost($userId) {
        $itor = $this->buildUserPostIterator($userId);
        $itor->setStep(10);
        foreach ($itor as $postIds) {
            foreach ($postIds as $postId) {
                $this->postService->undelete($postId);
            }
        }
    }

    private function buildUserCommentIterator($userId) {
        return new CustomizedCursorableIterator(function($cursor, $step, &$nextCursor) use($userId) {
            if ($step == 0) {
                $nextCursor = $cursor;
                return array();
            }

            $sql = 'SELECT id FROM comment WHERE id > ? AND author_id = ? AND deleted = 1 LIMIT ?';
            $stat = $this->connection->executeQuery($sql, array($cursor, $userId, $step),
                array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
            $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
            if (count($rows) < $step) {
                $nextCursor = 0;
            } else {
                $nextCursor = $rows[count($rows) - 1]['id'];
            }
            return ArrayUtility::columns($rows, 'id');
        });
    }

    public function recoverComment($userId) {
        $itor = $this->buildUserCommentIterator($userId);
        $itor->setStep(10);
        foreach ($itor as $commentIds) {
            foreach ($commentIds as $commentId) {
                $this->commentService->undelete($commentId);
            }
        }
    }

}