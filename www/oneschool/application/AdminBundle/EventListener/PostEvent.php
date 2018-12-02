<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 6/15/16
 * Time: 11:26 AM
 */

namespace Lychee\Bundle\AdminBundle\EventListener;


use Symfony\Component\EventDispatcher\Event;

class PostEvent extends Event {

    const DELETE = 'lychee.admin.post.delete';

    private $postId;

    private $adminId;

    public function __construct($postId, $adminId) {
        $this->postId = $postId;
        $this->adminId = $adminId;
    }

    /**
     * @return mixed
     */
    public function getPostId()
    {
        return $this->postId;
    }

    /**
     * @return mixed
     */
    public function getAdminId()
    {
        return $this->adminId;
    }
}