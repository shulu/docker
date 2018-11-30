<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Component\Content\UrlReplacer;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Like\LikeResolver;
use Lychee\Module\Favorite\FavoriteResolver;
use Lychee\Component\Foundation\ImageUtility;
use Lychee\Module\Post\ContentResolver;

class PostSynthesizer extends AbstractSynthesizer {
    /**
     * @var Synthesizer
     */
    private $authorSynthesizer;

    /**
     * @var Synthesizer
     */
    private $topicSynthesizer;

    /**
     * @var Synthesizer
     */
    private $countingSynthesizer;

    /**
     * @var LikeResolver
     */
    private $likeResolver;

    /**
     * @var Synthesizer
     */
    private $likerSynthesizer;

    private $favoriteResolver;

    /**
     * @var Synthesizer
     */
    private $latestCommentSynthesizer;

    private $imGroupSynthesizer;
    private $scheduleSynthesizer;
    private $votingSynthesizer;

    /**
     * @var ContentResolver
     */
    private $contentResolver;

    /**
     * @param array $postsByIds
     * @param Synthesizer|null $authorSynthesizer
     * @param Synthesizer|null $topicSynthesizer
     * @param Synthesizer|null $countingSynthesizer
     * @param LikeResolver|null $likeResolver
     * @param Synthesizer|null $likerSynthesizer
     * @param FavoriteResolver|null $favoriteResolver
     * @param Synthesizer|null $latestCommentSynthesizer
     * @param Synthesizer|null $imGroupSynthesizer
     * @param Synthesizer|null $scheduleSynthesizer
     * @param Synthesizer|null $votingSynthesizer
     * @param ContentResolver|null $contentResolver
     */
    public function __construct(
        $postsByIds, $authorSynthesizer, $topicSynthesizer,
        $countingSynthesizer, $likeResolver, $likerSynthesizer, $favoriteResolver, $latestCommentSynthesizer,
        $imGroupSynthesizer, $scheduleSynthesizer, $votingSynthesizer, $contentResolver=null
    ) {
        parent::__construct($postsByIds);
        $this->authorSynthesizer = $authorSynthesizer;
        $this->topicSynthesizer = $topicSynthesizer;
        $this->countingSynthesizer = $countingSynthesizer;
        $this->likeResolver = $likeResolver;
        $this->likerSynthesizer = $likerSynthesizer;
        $this->favoriteResolver = $favoriteResolver;
        $this->latestCommentSynthesizer = $latestCommentSynthesizer;
        $this->imGroupSynthesizer = $imGroupSynthesizer;
        $this->scheduleSynthesizer = $scheduleSynthesizer;
        $this->votingSynthesizer = $votingSynthesizer;
        $this->contentResolver = $contentResolver;
    }



    /**
     * @param Post $post
     * @param mixed $info
     * @return array
     */
    protected function synthesize($post, $info = null) {
        if ($post->deleted) {
            return array(
                'id' => $post->id,
                'content' => '抱歉，此消息已经被作者删除！',
                'deleted' => true
            );
        } else {
            $typeMap = array(
                Post::TYPE_NORMAL => 'picture',
                Post::TYPE_RESOURCE => 'resource',
                Post::TYPE_GROUP_CHAT => 'group_chat',
                Post::TYPE_SCHEDULE => 'schedule',
                Post::TYPE_VOTING => 'voting',
                Post::TYPE_VIDEO => 'video',
	            Post::TYPE_LIVE => 'live',
                Post::TYPE_SHORT_VIDEO => 'short_video',
            );

            $result = array(
                'id' => $post->id,
                'topic' => $post->topicId > 0 ?
                        ($this->topicSynthesizer ?
                            $this->topicSynthesizer->synthesizeOne($post->topicId):
                            array('id' => $post->topicId)):
                        null,
                'create_time' => $post->createTime->getTimestamp(),
                'type' => isset($typeMap[$post->type]) ? $typeMap[$post->type] : null,
                'title' => $post->title,
                'content' => $post->content,
                'image_url' => $post->imageUrl,
                'video_url' => $post->videoUrl,
                'audio_url' => $post->audioUrl,
                'site_url' => $post->siteUrl,
                'annotation' => json_decode($post->annotation),
                'geo' => ($post->longitude != null && $post->latitude != null) ?
                        array(
                            'longitude' => $post->longitude,
                            'latitude' => $post->latitude,
                            'address' => $post->address
                        ) :
                        null,
            );

            $updatedContent = null;

            if ($this->contentResolver) {
                $updatedContent = $this->contentResolver->get($post->id);
            }

            if (!is_null($updatedContent)) {
                $result['content'] = $updatedContent;
            }
//            短视频新功能兼容旧版方案
            if (Post::TYPE_SHORT_VIDEO==$post->type) {
                $result['content'] = "【短视频】 ".$result['content'];
            }
            if ($this->authorSynthesizer) {
                $result['author'] = $this->authorSynthesizer->synthesizeOne($post->authorId, $post->topicId);
                if (isset($result['topic']['manager']) && $result['topic']['manager']['id'] == $post->authorId) {
                    $result['author']['topic_title'] = '领主';
                }
            } else {
                $result['author'] = array('id' => $post->authorId);
            }

            if ($this->likerSynthesizer) {
                $result['latest_likers'] = $this->likerSynthesizer->synthesizeOne($post->id);
            } else {
                $result['latest_likers'] = array();
            }
            if ($this->countingSynthesizer) {
                $counting = $this->countingSynthesizer->synthesizeOne($post->id);
                if ($counting) {
                    $result = array_merge($result, $counting);
                }
            }
            if ($this->likeResolver) {
                $result['liked'] = $this->likeResolver->isLiked($post->id);
            }
            if ($this->favoriteResolver) {
                $result['favorited'] = $this->favoriteResolver->isFavorited($post->id);
            }
            if ($this->latestCommentSynthesizer) {
                $comments = $this->latestCommentSynthesizer->synthesizeOne($post->id);
                if ($comments) {
                    $result['latest_comments'] = $comments;
                }
            }
            if ($post->imGroupId && $this->imGroupSynthesizer) {
                $group = $this->imGroupSynthesizer->synthesizeOne($post->imGroupId);
                if ($group) {
                    $result['im_group'] = $group;
                }
            }
            if ($post->scheduleId && $this->scheduleSynthesizer) {
                $schedule = $this->scheduleSynthesizer->synthesizeOne($post->scheduleId);
                if ($schedule) {
                    $result['schedule'] = $schedule;
                }
            }
            if ($post->votingId && $this->votingSynthesizer) {
                $voting = $this->votingSynthesizer->synthesizeOne($post->votingId);
                if ($voting) {
                    $result['voting'] = $voting;
                }
            }
            return ArrayUtility::filterNonNull($result);
        }
    }

} 