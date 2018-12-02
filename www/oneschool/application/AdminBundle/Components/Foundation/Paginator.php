<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14-9-17
 * Time: 下午5:54
 */

namespace Lychee\Bundle\AdminBundle\Components\Foundation;
use Lychee\Component\Foundation\CursorableIterator\CursorableIterator;


/**
 * Class Paginator
 * @package Lychee\Bundle\AdminBundle\Components\Foundation
 */
class Paginator {

    /**
     * @var int
     */
    private $page = 1;

    /**
     * @var int
     */
    private $step = 20;

    /**
     * @var int
     */
    private $pageCount = 5;

    /**
     * @var int
     */
    private $startPageNum = 1;

    /**
     * @var \Lychee\Component\Foundation\CursorableIterator\CursorableIterator
     */
    private $iterator;

    private $maxPageCount;

    /**
     * @param CursorableIterator $iterator
     */
    public function __construct(CursorableIterator $iterator)
    {
        $this->iterator = $iterator;
        $this->maxPageCount = $this->pageCount;
        $this->setIteratorStep();
    }

    /**
     * @param $step
     * @return $this
     */
    public function setStep($step)
    {
        $this->step = $step;
        $this->setIteratorStep();

        return $this;
    }

    /**
     * @param $page
     * @return $this
     */
    public function setPage($page)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * @param $cursor
     * @return $this
     */
    public function setCursor($cursor)
    {
        $this->iterator->setCursor((int)$cursor);

        return $this;
    }

    /**
     * @param $pageCount
     * @return $this
     */
    public function setPageCount($pageCount)
    {
        $this->pageCount = $pageCount;
        $this->maxPageCount = $this->pageCount;
        $this->setIteratorStep();

        return $this;
    }

    /**
     * @return $this
     */
    private function setIteratorStep()
    {
        $this->iterator->setStep($this->step * $this->pageCount);

        return $this;
    }

    /**
     * @return array
     */
    public function getResult()
    {
        $result = $this->iterator->current();
        $count = count($result);
        if ($count < $this->pageCount * $this->step) {
            $this->maxPageCount = max(1, (int)ceil($count / $this->step));
        }
        $offset = $this->step * ($this->page - $this->startPageNum);

        return array_slice($result, $offset, $this->step, true);
    }

    /**
     * @return int
     */
    public function getMaxPageCount()
    {
        return $this->maxPageCount;
    }

    /**
     * @param $startPageNum
     * @return $this
     */
    public function setStartPageNum($startPageNum)
    {
        $this->startPageNum = $startPageNum;

        return $this;
    }

    /**
     * @return int
     */
    public function getPageCount()
    {
        return $this->pageCount;
    }

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @return int
     */
    public function getCursor()
    {
        return $this->iterator->getCursor();
    }

    /**
     * @return int
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * @return mixed
     */
    public function getNextCursor()
    {
        return $this->iterator->getNextCursor();
    }

    /**
     * @return int
     */
    public function getStartPageNum()
    {
        return $this->startPageNum;
    }

}