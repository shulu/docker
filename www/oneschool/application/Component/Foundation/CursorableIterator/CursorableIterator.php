<?php

namespace Lychee\Component\Foundation\CursorableIterator;


interface CursorableIterator extends \Iterator {
    /**
     * @param mixed $cursor
     *
     * @return CursorableIterator
     */
    public function setCursor($cursor);

    /**
     * @return mixed
     */
    public function getCursor();

    /**
     * @return mixed
     */
    public function getNextCursor();

    /**
     * @param int $step
     *
     * @return CursorableIterator
     */
    public function setStep($step);

    /**
     * @return int
     */
    public function getStep();
} 