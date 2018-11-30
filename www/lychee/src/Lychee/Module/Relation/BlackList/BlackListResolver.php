<?php
namespace Lychee\Module\Relation\BlackList;

interface BlackListResolver {
    /**
     * @param int $anotherId
     *
     * @return bool
     */
    public function isBlocking($anotherId);
} 