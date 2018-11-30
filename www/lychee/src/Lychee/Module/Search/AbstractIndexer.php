<?php
namespace Lychee\Module\Search;

use Elastica\Exception\NotFoundException;
use Elastica\Exception\ResponseException;
use Elastica\Type;
use Elastica\Document;

abstract class AbstractIndexer implements Indexer {

    protected $type;

    /**
     * @param Type $type
     */
    public function __construct($type) {
        $this->type = $type;
    }

    /**
     * @param $object
     *
     * @return Document
     */
    abstract protected function toDocument($object);

    /**
     * @param $object
     * @return mixed
     */
    abstract protected function getId($object);

    /**
     * @param object|object[] $object
     */
    public function add($object) {
        try {
            if (is_array($object)) {
                $documents = array();
                foreach ($object as $obj) {
                    $document = $this->toDocument($obj);
                    if ($document) {
                        $documents[] = $document;
                    }
                }

                $this->type->addDocuments($documents);
            } else {
                $document = $this->toDocument($object);
                if ($document) {
                    $this->type->addDocument($document);
                }
            }
        } catch (ResponseException $e) {
            //do nothing
        } catch (\Elastica\Exception\Bulk\ResponseException $e) {
            //do nothing
        }
    }

    /**
     * @param object|object[] $object
     */
    public function update($object) {
        if (is_array($object)) {
            $toUpdates = array();
            $toDeletes = array();
            foreach ($object as $obj) {
                $document = $this->toDocument($obj);
                if ($document) {
                    $toUpdates[] = $document;
                } else {
                    $toDeletes[] = $obj;
                }
            }
            if (count($toDeletes) > 0) {
                $this->remove($toDeletes);
            }
            if (count($toUpdates) > 0) {
                $this->type->updateDocuments($toUpdates);
            }
        } else {
            $document = $this->toDocument($object);
            if (!$document) {
                $this->remove($object);
            } else {
                $this->type->updateDocument($document);
            }
        }
    }

    /**
     * @param object|object[]|int|int[] $objectOrId
     */
    public function remove($objectOrId) {
        try {
            if (is_array($objectOrId)) {
                $ids = array();
                foreach ($objectOrId as $obj) {
                    if (is_object($objectOrId)) {
                        $ids[] = $this->getId($obj);
                    } else {
                        $ids[] = $obj;
                    }
                }
                $this->type->deleteIds($ids);
            } else {
                if (is_object($objectOrId)) {
                    $id = $this->getId($objectOrId);
                } else {
                    $id = $objectOrId;
                }
                $this->type->deleteById($id);
            }
        } catch (NotFoundException $e) {
            //do nothing
        } catch (ResponseException $e) {
            //do nothing
        } catch (\Elastica\Exception\Bulk\ResponseException $e) {
            //do nothing
        }
    }
}