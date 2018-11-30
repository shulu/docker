<?php
namespace Lychee\Module\Search;

use Elastica\Document;
use Lychee\Bundle\CoreBundle\Entity\User;

class UserIndexer extends AbstractIndexer {
    /**
     * @param User $object
     *
     * @return Document
     */
    protected function toDocument($object) {
        return new Document($object->id, array('nickname' => $object->nickname));
    }

    /**
     * @param User $object
     *
     * @return mixed
     */
    protected function getId($object) {
        return $object->id;
    }
}