<?php
namespace Lychee\Component\Foundation\CursorableIterator;

class ArrayCursorableIterator extends AbstractCursorableIterator {

    protected $array;

    public function __construct($array) {
        $this->array = $array;
    }

    /**
     * @param mixed $cursor
     * @param int   $step
     * @param mixed $nextCursor
     *
     * @return mixed
     */
    protected function getResult($cursor, $step, &$nextCursor) {
        if ($cursor + $step >= count($this->array)) {
            $resultCount = count($this->array) - $cursor;
            $nextCursor = 0;
            if ($resultCount <= 0) {
                return array();
            }
            return array_slice($this->array, $cursor, $resultCount);
        } else {
            $nextCursor = $cursor + $step;
            return array_slice($this->array, $cursor, $step);
        }
    }
} 