<?php
namespace Lychee\Component\Foundation\CursorableIterator;

use Doctrine\ORM\Query;
use Lychee\Component\Foundation\ArrayUtility;

/**
 * Class DatetimeQueryCursorableIterator
 * @package Lychee\Component\Foundation\CursorableIterator
 */
class DatetimeQueryCursorableIterator extends QueryCursorableIterator {

    /**
     * @var
     */
    protected $cursor;

    /**
     * @var
     */
    private $initCursor;

    /**
     * @param Query $query
     * @param string $cursorFieldName
     * @param null $resultFieldName
     * @param \DateTime $initCursor
     */
    public function __construct($query, $cursorFieldName, $resultFieldName = null, \DateTime $initCursor = null)
    {
        parent::__construct($query, $cursorFieldName, $resultFieldName);
        if (null !== $initCursor) {
            $this->initCursor = $initCursor;
        } else {
            $this->initCursor = new \DateTime('1970-01-01');
        }
        $this->cursor = $this->initCursor;
    }

    /**
     *
     */
    public function next()
    {
        if ($this->nextCursor !== null) {
            $this->cursor = $this->nextCursor;
            $this->nextCursor = null;
            $this->result = null;
        } else {
            $this->getResult($this->cursor, $this->step, $nextCursor);
            $this->cursor = $nextCursor;
        }

        if ($this->cursor === $this->initCursor) {
            $this->reachEnd = true;
        }
    }

    /**
     *
     */
    public function rewind()
    {
        $this->reachEnd = false;
        $this->cursor = $this->initCursor;
    }

    /**
     * @param int $cursor
     * @return $this
     */
    public function setCursor($cursor)
    {
        $this->cursor = $cursor;
        $this->result = null;
        $this->nextCursor = null;
        $this->reachEnd = false;

        return $this;
    }

    /**
     * @param mixed $cursor
     * @param int $step
     * @param mixed $nextCursor
     * @return array
     */
    protected function getResult($cursor, $step, &$nextCursor)
    {
        if ($step <= 0) {
            return array();
        }

        $this->query->setParameter($this->cursorParameterName, $cursor);
        $this->query->setMaxResults($step);

        $result = $this->query->getResult();

        if (count($result) < $step) {
            $nextCursor = $this->initCursor;
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