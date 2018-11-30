<?php
namespace Lychee\Component\Foundation\CursorableIterator;

use Doctrine\ORM\Query;
use Lychee\Component\Foundation\ArrayUtility;

class QueryCursorableIterator extends AbstractCursorableIterator {

    const ORDER_ASC = 1;
    const ORDER_DESC = 2;

    /**
     * @var Query
     */
    protected $query;

    /**
     * @var string
     */
    protected $cursorFieldName;

    /**
     * @var string
     */
    protected $cursorParameterName;

    /**
     * @var string
     */
    protected $resultFieldName;

    /**
     * @var int
     */
    private $order;

    /**
     * @param Query $query
     * @param string $cursorFieldName
     * @param string $resultFieldName
     * @param int $order
     * @throws \LogicException
     */
    public function __construct($query, $cursorFieldName, $resultFieldName = null, $order = self::ORDER_ASC) {
        $this->query = $query;
        $this->cursorFieldName = $cursorFieldName;
//        if ($this->query->getParameter('cursor') !== null) {
            $this->cursorParameterName = 'cursor';
//        } else if ($this->query->getParameter($cursorFieldName) !== null) {
//            $this->cursorParameterName = $cursorFieldName;
//        } else {
//            throw new \LogicException('can not guest the cursor parameter name');
//        }

        $this->resultFieldName = $resultFieldName;
        $this->order = $order;
    }

    /**
     * @param mixed $cursor
     * @param int   $step
     * @param mixed $nextCursor
     *
     * @return mixed
     */
    protected function getResult($cursor, $step, &$nextCursor) {
        if ($step <= 0) {
            return array();
        }

        if ($this->order === self::ORDER_DESC && $cursor === 0) {
            $cursor = PHP_INT_MAX;
        }

        $this->query->setParameter($this->cursorParameterName, $cursor);
        $this->query->setMaxResults($step);

        $result = $this->query->getResult();

        if (count($result) < $step) {
            $nextCursor = 0;
        } else {
            $lastEntity = $result[count($result) - 1];
            if (is_array($lastEntity)) {
                $nextCursor = $lastEntity[$this->cursorFieldName];
            } else {
                $nextCursor = $lastEntity->{$this->cursorFieldName};
            }
        }

        if ($this->resultFieldName === null) {
            return $result;
        } else {
            return ArrayUtility::columns($result, $this->resultFieldName);
        }
    }
} 