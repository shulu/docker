<?php
namespace Lychee\Module\Search;

use Elastica\Document;
use Lychee\Module\Topic\Entity\Topic;

class TopicIndexer extends AbstractIndexer {

    /**
     * @param Topic $object
     *
     * @return Document
     */
    protected function toDocument($object) {
        if ($object->hidden) {
            return null;
        } else {
            return new Document($object->id, array(
                'title' => $object->title,
                'certified' => $object->certified
            ));
        }
    }

    /**
     * @param Topic $object
     *
     * @return mixed
     */
    protected function getId($object) {
        return $object->id;
    }
}