<?php
namespace Lychee\Module\IM;

class Message {
    /**
     * @var int
     */
    public $from;

    /**
     * @var int
     */
    public $to;

    /**
     * @var int
     */
    public $time;

    /**
     * @var int
     */
    public $type;

    /**
     * @var mixed
     */
    public $body;
}