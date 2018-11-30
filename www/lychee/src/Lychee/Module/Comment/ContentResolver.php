<?php
namespace Lychee\Module\Comment;

class ContentResolver{

    private $map;

    public function __construct($map) {
        $this->map = $map;
    }

    public function get($id)
    {
        if (!isset($this->map[$id])) {
            return null;
        }
        return $this->map[$id];
    }

} 