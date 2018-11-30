<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

class TopicCategorySynthesizer extends AbstractSynthesizer {

    private $attributesMap = array(
        'meng' => '萌',
        'ran' => '燃',
        'zhai' => '宅',
        'fu' => '腐',
        'jian' => '贱',
        'ao' => '傲',
    );

    public function __construct($categoriesByTopicIds) {
        $this->entitiesByIds = $categoriesByTopicIds;
    }

    /**
     * @param string[] $entity
     * @param mixed $info
     * @return array
     */
    protected function synthesize($entity, $info = null) {
        $attributes = array();
        $categories = array();
        foreach ($entity as $i) {
            if (isset($this->attributesMap[$i])) {
                $attributes[] = $this->attributesMap[$i];
            } else {
                $categories[] = $i;
            }
        }

        return array(
            'attribute' => count($attributes) > 0 ? current($attributes) : null,
            'categories' => count($categories) > 0 ? $categories : null,
        );
    }
}