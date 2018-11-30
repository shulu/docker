<?php
namespace Lychee\Module\Topic;

class TopicParameter {

    /**
     * @var int
     */
    public $creatorId = null;

    /**
     * @var string
     */
    public $title = null;

    /**
     * @var int[]
     */
    public $categoryIds = array();

    /**
     * @var string
     */
    public $summary = null;

    /**
     * @var string
     */
    public $description = null;

    /**
     * @var string
     */
    public $indexImageUrl = null;

    /**
     * @var string
     */
    public $coverImageUrl = null;

    /**
     * @var bool
     */
    public $private = false;

    /**
     * @var bool
     */
    public $applyToFollow = false;

    /**
     * @var string
     */
    public $color = null;
    
}