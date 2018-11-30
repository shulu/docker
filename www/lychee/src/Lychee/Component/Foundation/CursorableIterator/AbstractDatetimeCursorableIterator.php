<?php
namespace Lychee\Component\Foundation\CursorableIterator;

use Assetic\Factory\Resource\DirectoryResourceIterator;

abstract class AbstractDatetimeCursorableIterator implements CursorableIterator {

    /**
     * @var \DateTime
     */
    protected $cursor;

    /**
     * @var int
     */
    protected $step = 20;

    /**
     * @var boolean
     */
    protected $reachEnd;

    /**
     * @var mixed
     */
    protected $result;

    /**
     * @var mixed
     */
    protected $nextCursor;


    /**
     * @param mixed $cursor
     * @param int $step
     * @param mixed $nextCursor
     *
     * @return mixed
     */
    abstract protected function getResult($cursor, $step, &$nextCursor);

    /**
     * @return mixed Can return any type.
     */
    public function current() {
        if (!$this->result) {
            $this->result = $this->getResult($this->cursor, $this->step, $nextCursor);
            $this->nextCursor = $nextCursor;
        }

        return $this->result;
    }

    /**
     * @return void Any returned value is ignored.
     */
    public function next() {
        if ($this->nextCursor !== null) {
            $this->cursor = $this->nextCursor;
            $this->nextCursor = null;
            $this->result = null;
        } else {
            $this->getResult($this->cursor, $this->step, $nextCursor);
            $this->cursor = $nextCursor;
        }

        if ($this->cursor === 0) {
            $this->reachEnd = true;
        }
    }

    /**
     * @return mixed scalar on success, or null on failure.
     */
    public function key() {
        return $this->cursor;
    }

    /**
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid() {
        return $this->reachEnd === false && $this->step > 0;
    }

    /**
     * @return void Any returned value is ignored.
     */
    public function rewind() {
        $this->reachEnd = false;
        $this->cursor = 0;
    }

    /**
     * @param int $cursor
     *
     * @return CursorableIterator
     */
    public function setCursor($cursor) {
        $this->cursor = $cursor;
        $this->result = null;
        $this->nextCursor = null;
        $this->reachEnd = false;
        return $this;
    }

    /**
     * @return int
     */
    public function getCursor() {
        return $this->cursor;
    }

    /**
     * @return mixed
     */
    public function getNextCursor() {
        if (!$this->nextCursor) {
            $this->result = $this->getResult($this->cursor, $this->step, $nextCursor);
            $this->nextCursor = $nextCursor;
        }

        return $this->nextCursor;
    }

    /**
     * @param int $step
     *
     * @return CursorableIterator
     */
    public function setStep($step) {
        $this->step = $step;
        $this->result = null;
        $this->nextCursor = null;
        return $this;
    }

    /**
     * @return int
     */
    public function getStep() {
        return $this->step;
    }

} 