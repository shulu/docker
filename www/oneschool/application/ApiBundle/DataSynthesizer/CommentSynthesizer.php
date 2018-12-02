<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Bundle\CoreBundle\Entity\Comment;
use Lychee\Component\Content\UrlReplacer;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Comment\ContentResolver;
use Lychee\Module\Like\LikeResolver;

class CommentSynthesizer extends AbstractSynthesizer {

    private $postSynthesizer;

    private $authorSynthesizer;

    private $repliedSynthesizer;

    /**
     * @var LikeResolver
     */
    private $likeResolver;

    /**
     * @var ContentResolver
     */
    private $contentResolver;

    /**
     * @param array $commentsByIds
     * @param Synthesizer|null $authorSynthesizer
     * @param Synthesizer|null $repliedSynthesizer
     * @param Synthesizer|null $postSynthesizer
     */
    public function __construct(
        $commentsByIds,
        $authorSynthesizer,
        $repliedSynthesizer,
        $postSynthesizer,
        $likeResolver=null,
        $contentResolver=null
    ) {
        parent::__construct($commentsByIds);
        $this->authorSynthesizer = $authorSynthesizer;
        $this->repliedSynthesizer = $repliedSynthesizer;
        $this->postSynthesizer = $postSynthesizer;
        $this->likeResolver = $likeResolver;
        $this->contentResolver = $contentResolver;
    }

    /**
     * @param Comment $comment
     * @param mixed $info
     *
     * @return array
     */
    protected function synthesize($comment, $info = null) {
        if ($comment->deleted) {
            return array(
                'id' => $comment->id,
                'author' => $this->authorSynthesizer ?
                    $this->authorSynthesizer->synthesizeOne($comment->authorId) :
                    array('id' => $comment->authorId)
                ,
                'content' => '抱歉，此评论已经被删除！',
            );
        } else {
            $result = array(
                'id' => $comment->id,
                'author' => $this->authorSynthesizer ?
                    $this->authorSynthesizer->synthesizeOne($comment->authorId) :
                    array('id' => $comment->authorId)
                ,
                'post' => $this->postSynthesizer ?
                        $this->postSynthesizer->synthesizeOne($comment->postId):
                        array('id' => $comment->postId),
                'replyed' => $comment->repliedId ?
                    ($this->repliedSynthesizer ?
                        $this->repliedSynthesizer->synthesizeOne($comment->repliedId):
                        array('id' => $comment->repliedId)
                    ) :
                    null
                ,
                'create_time' => $comment->createTime->getTimestamp(),
                'district' => $comment->district,
                'content' => $comment->content,
                'image_url' => $comment->imageUrl,
                'liked_count' => $comment->likedCount,
                'annotation' => json_decode($comment->annotation)
            );
            if ($this->likeResolver) {
                $result['liked'] = $this->likeResolver->isLiked($comment->id);
            }

            $updatedContent = null;

            if ($this->contentResolver) {
                $updatedContent = $this->contentResolver->get($comment->id);
            }

            if (!is_null($updatedContent)) {
                $result['content'] = $updatedContent;
            }

            return ArrayUtility::filterNonNull($result);
        }
    }

} 