<?php
namespace Lychee\Component\Foundation\CursorableIterator;

class CustomizedCursorableIterator extends AbstractCursorableIterator {

    protected $customizedFunction;

    /**
     * @param Callback $customizedFunction function with ($cursor, $step, &$nextCursor) parameters
     */
    public function __construct($customizedFunction) {
        $this->customizedFunction = $customizedFunction;
    }

    /**
     * @param mixed $cursor
     * @param int   $step
     * @param mixed $nextCursor
     *
     * @return mixed
     */
    protected function getResult($cursor, $step, &$nextCursor) {
        return call_user_func_array($this->customizedFunction, array($cursor, $step, &$nextCursor));
    }

} 