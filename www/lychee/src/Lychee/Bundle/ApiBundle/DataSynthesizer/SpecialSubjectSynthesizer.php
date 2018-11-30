<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Module\Recommendation\Entity\SpecialSubject;

class SpecialSubjectSynthesizer extends AbstractSynthesizer {

    private $postSynthesizer;
    private $topicSynthesizer;
    private $userSynthesizer;

    /**
     * @param array $subjectsByIds
     * @param Synthesizer|null $postSynthesizer
     * @param Synthesizer|null $topicSynthesizer
     * @param Synthesizer|null $userSynthesizer
     */
    public function __construct(
        $subjectsByIds,
        $postSynthesizer,
        $topicSynthesizer,
        $userSynthesizer
    ) {
        parent::__construct($subjectsByIds);
        $this->postSynthesizer = $postSynthesizer;
        $this->topicSynthesizer = $topicSynthesizer;
        $this->userSynthesizer = $userSynthesizer;
    }

    /**
     * @param SpecialSubject $entity
     * @param mixed $info
     * @return array
     */
    protected function synthesize($entity, $info = null) {
        $result = array(
            'id' => $entity->getId(),
            'name' => $entity->getName(),
            'banner_url' => $entity->getBanner(),
            'description' => $entity->getDescription(),
        );

        if ($this->postSynthesizer) {
            $posts = $this->postSynthesizer->synthesizeOne($entity->getId());
            if (!empty($posts)) {
                $result['posts'] = $posts;
            }
        }

        if ($this->topicSynthesizer) {
            $topics = $this->topicSynthesizer->synthesizeOne($entity->getId());
            if (!empty($topics)) {
                $result['topics'] = $topics;
            }
        }

        if ($this->userSynthesizer) {
            $users = $this->userSynthesizer->synthesizeOne($entity->getId());
            if (!empty($users)) {
                $result['users'] = $users;
            }
        }

        return $result;
    }
}