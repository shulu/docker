<?php
namespace Lychee\Module\Search;

use Elastica\Document;
use Elastica\Type;
use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Module\Topic\TopicService;

class PostIndexer extends AbstractIndexer {

    private $topicService;

    /**
     * PostIndexer constructor.
     *
     * @param Type $type
     * @param TopicService $topicService
     */
    public function __construct(Type $type, $topicService) {
        parent::__construct($type);
        $this->topicService = $topicService;
    }


    /**
     * @param Post $object
     *
     * @return Document
     */
    protected function toDocument($object) {
        if (strlen($object->content) > 0 && $object->deleted == false && $object->topicId > 0) {
            $topic = $this->topicService->fetchOne($object->topicId);
            if ($topic == null || $topic->deleted) {
                return null;
            }

            $resourceTitle = null;
            if ($object->type == Post::TYPE_RESOURCE) {
                $annotation = json_decode($object->annotation, true);
                if ($annotation !== null
                    && isset($annotation['resource_title'])
                    && !empty($annotation['resource_title'])
                ) {
                    $resourceTitle = $annotation['resource_title'];
                }
            }

            $data = array(
                'topic_id' => intval($object->topicId),
                'topic_private' => boolval($topic->hidden || $topic->private),
                'author_id' => intval($object->authorId),
                'content' => $object->content,
                'create_time' => $object->createTime->format('Y-m-d\TH:i:s')
            );

            if ($resourceTitle !== null) {
                $data['resource'] = $resourceTitle;
                return new Document($object->id, $data);
            } else {
                return new Document($object->id, $data);
            }

        } else {
            return null;
        }
    }

    /**
     * @param Post $object
     *
     * @return mixed
     */
    protected function getId($object) {
        return $object->id;
    }

}