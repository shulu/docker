<?php

namespace Lychee\Module\Search;


interface Indexer {
    /**
     * @param mixed $object
     */
    public function add($object);

    /**
     * @param mixed $object
     */
    public function update($object);

    /**
     * @param $object
     */
    public function remove($object);
}