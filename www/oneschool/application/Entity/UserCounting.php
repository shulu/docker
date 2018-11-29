<?php
namespace app\entity;


/**
 * @Entity()
 * @Table(name="user_counting")
 */
class UserCounting {
    /**
     * @var int
     *
     * @Column(name="user_id", type="bigint")
     * @Id
     */
    public $userId;

    /**
     * @var int
     *
     * @Column(name="post_count", type="integer")
     */
    public $postCount = 0;

    /**
     * @var int
     *
     * @Column(name="image_comment_count", type="integer")
     */
    public $imageCommentCount = 0;
} 