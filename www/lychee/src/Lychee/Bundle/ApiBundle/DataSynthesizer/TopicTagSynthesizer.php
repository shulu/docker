<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Module\Topic\Entity\TopicTag;

class TopicTagSynthesizer extends AbstractSynthesizer {

    public function __construct($tagsByTopicIds) {
        parent::__construct($tagsByTopicIds);
    }

    /**
     * @param string[] $entity
     * @param mixed $info
     * @return array
     */
    protected function synthesize($entity, $info = null) {
        $tags = array();
        foreach ($entity as $i) {
            /** @var TopicTag $i */
            $tags[] = array(
                'name' => $i->name,
                'color' => $i->color,
            );
        }

        return array(
            'tags' => count($tags) > 0 ? $tags : null,
        );
    }
}