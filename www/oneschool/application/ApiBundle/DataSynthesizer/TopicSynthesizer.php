<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Module\Topic\Entity\Topic;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Topic\Following\FollowingResolver;
use Lychee\Module\Topic\Following\Counter;
use Lychee\Component\Foundation\ImageUtility;

class TopicSynthesizer extends AbstractSynthesizer {
    /**
     * @var FollowingResolver
     */
    private $followingResolver;

    /**
     * @var TopicFollowerSynthesizer
     */
    private $newestFollowerSynthesizer;

    private $managerSynthesizer;

    private $categorySynthesizer;

    /**
     * @param array $topicsByIds
     * @param FollowingResolver|null $followingResolver
     * @param TopicFollowerSynthesizer|null $newestFollowerSynthesizer
     * @param Synthesizer|null $managerSynthesizer
     * @param Synthesizer|null $categorySynthesizer
     */
    public function __construct(
        $topicsByIds,
        $followingResolver,
        $newestFollowerSynthesizer,
        $managerSynthesizer,
        $categorySynthesizer
    ) {
        parent::__construct($topicsByIds);
        $this->followingResolver = $followingResolver;
        $this->newestFollowerSynthesizer = $newestFollowerSynthesizer;
        $this->managerSynthesizer = $managerSynthesizer;
        $this->categorySynthesizer = $categorySynthesizer;
    }

    /**
     * @param Topic $topic
     * @param mixed $info
     * @return array
     */
    protected function synthesize($topic, $info = null) {
        $result = array(
            'id' => $topic->id,
            'create_time' => $topic->createTime->getTimestamp(),
            'title' => $topic->title,
            'summary' => $topic->summary,
            'description' => $topic->description,
            'index_image' => $topic->indexImageUrl,
            'cover_image' => $topic->coverImageUrl,
            'post_count' => $topic->postCount,
            'followers_count' => $topic->followerCount,
            'private' => $topic->private,
            'apply_to_follow' => $topic->private,
            'color' => $topic->color,
            'certified' => $topic->certified,
	        'link' => $topic->link,
	        'link_title' => $topic->linkTitle
        );

        if ($this->followingResolver) {
            $result['following'] = $this->followingResolver->isFollowing($topic->id);
        }
        if ($this->newestFollowerSynthesizer) {
            $result['newest_followers'] = $this->newestFollowerSynthesizer
                ->synthesizeOne($topic->id, $topic->id);
        }
        if ($topic->managerId) {
            if ($this->managerSynthesizer) {
                $result['manager'] = $this->managerSynthesizer->synthesizeOne($topic->managerId);
            } else {
                $result['manager'] = array('id' => $topic->managerId);
            }
        }
        if ($this->categorySynthesizer) {
            $categoriesInfo = $this->categorySynthesizer->synthesizeOne($topic->id);
            if ($categoriesInfo && $categoriesInfo['categories']) {
                $result = array_merge($result, $categoriesInfo);
            }
        }

        return ArrayUtility::filterNonNull($result);
    }

} 