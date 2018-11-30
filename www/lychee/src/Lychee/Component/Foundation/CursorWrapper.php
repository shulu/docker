<?php
namespace Lychee\Component\Foundation;

class CursorWrapper implements \Iterator {

    private $cursorfunction;

    private $currentCursor;

    private $nextCursor;

    private $count;

    private $end = false;

    private $currentResult;

    public function __construct($function, $count, $cursor = 0) {
        $this->cursorfunction = $function;
        $this->count = $count;
        $this->currentCursor = $cursor;
    }

    /**
     * @return mixed Can return any type.
     */
    public function current() {
        if (!$this->currentResult) {
            $nextCursor = null;
            $function = $this->cursorfunction;
            $this->currentResult = $function($this->currentCursor, $this->count, $nextCursor);
            $this->nextCursor = $nextCursor;
        }

        return $this->currentResult;
    }

    /**
     * @return void Any returned value is ignored.
     */
    public function next() {
        if ($this->nextCursor !== null) {
            $this->currentCursor = $this->nextCursor;
            $this->nextCursor = null;
            $this->currentResult = null;
        } else {
            $nextCursor = null;
            $function = $this->cursorfunction;
            $this->currentResult = $function($this->currentCursor, $this->count, $nextCursor);
            $this->currentCursor = $nextCursor;
        }

        if ($this->currentCursor === 0) {
            $this->end = true;
        }
    }

    /**
     * @return mixed scalar on success, or null on failure.
     */
    public function key() {
        return $this->currentCursor;
    }

    /**
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid() {
        return $this->cursorfunction && $this->end === false && $this->count > 0;
    }

    /**
     * @return void Any returned value is ignored.
     */
    public function rewind() {
        $this->end = false;
        $this->currentCursor = 0;
    }

} 