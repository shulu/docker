<?php
namespace Lychee\Module\Topic\Following;

class Counter {

    private $countsByIds;

    public function __construct($countsByIds) {
        $this->countsByIds = $countsByIds;
    }

    public function getCount($id) {
        if (isset($this->countsByIds[$id])) {
            return intval($this->countsByIds[$id]);
        } else {
            return 0;
        }
    }

}