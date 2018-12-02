<?php
namespace Lychee\Module\Recommendation\Post;

abstract class AbstractGroupResolver implements GroupResolver {

    protected $groupIds;

    /**
     * AbstractGroupResolver constructor.
     * @param int|int[] $groupIds
     */
    public function __construct($groupIds) {
        if (is_array($groupIds)) {
            $this->groupIds = $groupIds;
        } else {
            $this->groupIds = array($groupIds);
        }
    }

}