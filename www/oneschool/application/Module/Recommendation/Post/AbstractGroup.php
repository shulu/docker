<?php
namespace Lychee\Module\Recommendation\Post;

abstract class AbstractGroup implements Group {

    private $_id;
    private $_name;

    /**
     * AbstractGroup constructor.
     * @param int $id
     * @param string $name
     */
    public function __construct($id, $name) {
        $this->_id = $id;
        $this->_name = $name;
    }

    public function id() {
        return $this->_id;
    }

    public function name() {
        return $this->_name;
    }

}