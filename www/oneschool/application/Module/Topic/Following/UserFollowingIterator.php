<?php

namespace Lychee\Module\Topic\Following;

use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Foundation\CursorableIterator\AbstractCursorableIterator;
use Doctrine\DBAL\Connection;

class UserFollowingIterator extends AbstractCursorableIterator
{

    const ORDER_ASC = 1;
    const ORDER_DESC = 2;
    const ORDER_VISIT_DESC = 3;

    private $conn;
    private $userId;
    private $returnFavorite = false;
    private $order = self::ORDER_DESC;

    /**
     * @param Connection $conn
     * @param int $userId
     */
    public function __construct($conn, $userId)
    {
        $this->conn = $conn;
        $this->userId = $userId;
    }

    public function withFavorite($with)
    {
        $this->returnFavorite = $with;
    }

    public function withOrder($order)
    {

        $this->order = self::ORDER_ASC;
        if (!in_array($order, [self::ORDER_ASC, self::ORDER_DESC, self::ORDER_VISIT_DESC])) {
            return false;
        }
        $this->order = $order;
    }

//    按浏览次数降序排
    protected function getResultOrderByVisitDesc($cursor, $step, &$nextCursor)
    {
        $sql = <<<'SQL'
SELECT f.state, f.position, f.topic_id 
FROM topic_user_following f 
LEFT JOIN topic_visitor_counting  c  ON f.user_id = c.user_id AND f.topic_id = c.topic_id
WHERE f.user_id = ? AND state = 1 order by c.count DESC, f.position asc LIMIT ?,?
SQL;
        $stat = $this->conn->executeQuery($sql, array($this->userId, $cursor, $step+1),
            array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        $nextCursor = 0;
        if (isset($rows[$step])) {
            $nextCursor = $cursor+$step;
            unset($rows[$step]);
        }
        return ArrayUtility::columns($rows, 'topic_id');
    }


    protected function getResult($cursor, $step, &$nextCursor)
    {
        if ($step == 0) {
            $nextCursor = $cursor;
            return array();
        }

        switch ($this->order) {
            case self::ORDER_VISIT_DESC:
                return $this->getResultOrderByVisitDesc($cursor, $step, $nextCursor);
                break;
        }


        if ($cursor == 0) {
            //初始化
            if ($this->order == self::ORDER_DESC) {
                $favoritePos = PHP_INT_MAX;
                $normalPos = PHP_INT_MAX;
            } else {
                $favoritePos = 0;
                $normalPos = 0;
            }
        } else if ($cursor < 0) {
            //还在迭代最爱次元
            $favoritePos = abs($cursor);
            if ($this->order == self::ORDER_DESC) {
                $normalPos = PHP_INT_MAX;
            } else {
                $normalPos = 0;
            }
        } else {
            //迭代普通的次元中
            $favoritePos = null;
            $normalPos = $cursor;
        }

        if ($favoritePos !== null) {
            if ($this->order == self::ORDER_DESC) {
                $sql = 'SELECT state, position, topic_id FROM topic_user_following '
                    . 'WHERE user_id = ? AND ((state = 2 AND position < ?) OR (state = 1 AND position < ?)) '
                    . 'ORDER BY state DESC, position DESC LIMIT ?';
            } else {
                $sql = 'SELECT state, position, topic_id FROM topic_user_following '
                    . 'WHERE user_id = ? AND ((state = 2 AND position > ?) OR (state = 1 AND position > ?)) '
                    . 'ORDER BY state DESC, position ASC LIMIT ?';
            }

            $stat = $this->conn->executeQuery($sql, array($this->userId, $favoritePos, $normalPos, $step),
                array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
        } else {
            if ($this->order == self::ORDER_DESC) {
                $sql = 'SELECT state, position, topic_id FROM topic_user_following '
                    . 'WHERE user_id = ? AND state = 1 AND position < ? '
                    . 'ORDER BY state DESC, position DESC LIMIT ?';
            } else {
                $sql = 'SELECT state, position, topic_id FROM topic_user_following '
                    . 'WHERE user_id = ? AND state = 1 AND position > ? '
                    . 'ORDER BY state DESC, position ASC LIMIT ?';
            }

            $stat = $this->conn->executeQuery($sql, array($this->userId, $normalPos, $step),
                array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
        }
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        if (count($rows) < $step) {
            $nextCursor = 0;
        } else {
            $lastRow = $rows[count($rows) - 1];
            if ($lastRow['state'] == 2) {
                $nextCursor = -$lastRow['position'];
            } else {
                $nextCursor = $lastRow['position'];
            }
        }

        if ($this->returnFavorite) {
            return array_map(function ($row) {
                return array('topic_id' => $row['topic_id'], 'favorite' => $row['state'] == 2);
            }, $rows);
        } else {
            return ArrayUtility::columns($rows, 'topic_id');
        }
    }

}