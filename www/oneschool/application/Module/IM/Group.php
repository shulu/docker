<?php
namespace Lychee\Module\IM;

class Group {
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $icon;

    /**
     * @var string
     */
    public $description;

    /**
     * @var int
     */
    public $topicId;

    /**
     * @var int
     */
    public $postId;

    /**
     * @var \DateTime
     */
    public $createTime;

    /**
     * @var int
     */
    public $memberCount;

    /**
     * @var int[]
     */
    public $memberIds;

    /**
     * @var boolean|null
     */
    public $noDisturb;
}