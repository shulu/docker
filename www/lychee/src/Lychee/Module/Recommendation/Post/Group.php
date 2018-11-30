<?php
namespace Lychee\Module\Recommendation\Post;

interface Group {
    /**
     * @return int
     */
    public function id();

    /**
     * @return string
     */
    public function name();

    /**
     * @return GroupResolver
     */
    public function resolver();
}