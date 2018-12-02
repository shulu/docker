<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 5/26/16
 * Time: 8:52 PM
 */

namespace Lychee\Module\ContentAudit;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Lychee\Bundle\CoreBundle\Entity\Comment;
use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Module\Comment\CommentEvent;
use Lychee\Module\Comment\CommentService;
use Lychee\Module\ContentAudit\Entity\AntiRubbish;
use Lychee\Module\Post\PostEvent;
use Lychee\Module\Post\PostService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AntiRubbishEventSubscriber implements EventSubscriberInterface {

    /**
     * @var PostService $postService
     */
    private $postService;

    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var CommentService
     */
    private $commentService;

    public static function getSubscribedEvents() {
        return [
            PostEvent::CREATE => 'onPostEvent',
            CommentEvent::CREATE => 'onCommentEvent',
        ];
    }
    
    public function __construct (
        Registry $doctrine,
        PostService $postService,
        CommentService $commentService
    ) {
        $this->doctrine = $doctrine;
        $this->postService = $postService;
        $this->commentService = $commentService;
    }

    public function onPostEvent(PostEvent $e) {
        $contentLength = 30;
        $postId = $e->getPostId();
        $post = $this->postService->fetchOne($postId);
        if (!$this->isOfficialAccount($post->authorId)) {
            if ($this->isNumbers($post->content)) {
                $this->setRubbish(AntiRubbish::TYPE_POST, $post->authorId, $post->id);
            } else {
                if ($post->content && strlen($post->content) < $contentLength) {
                    return null;
                }
                $authorId = $post->authorId;
                $postIds = $this->postService->fetchIdsByAuthorId($authorId, $post->id, 2);
                $contents = [];
                $contents[] = [$post->id, $post->content];
                $posts = $this->postService->fetch($postIds);
                /** @var Post $p */
                foreach ($posts as $p) {
                    $contents[] = [$p->id, $p->content];
                }
                $this->process(AntiRubbish::TYPE_POST, $post, $contents);
            }
        }
    }

    public function onCommentEvent(CommentEvent $e) {
        $contentLength = 20;
        $commentId = $e->getCommentId();
        $comment = $this->commentService->fetchOne($commentId);
        if (!$this->isOfficialAccount($comment->authorId)) {
            if ($this->isNumbers($comment->content)) {
                $this->setRubbish(AntiRubbish::TYPE_COMMENT, $comment->authorId, $comment->id);
            } else {
                if ($comment->content && strlen($comment->content) < $contentLength) {
                    return null;
                }
                $authorId = $comment->authorId;
                $commentIds = $this->commentService->fetchIdsByAuthorId($authorId, $commentId, 2);
                $contents = [];
                $contents[] = [$commentId, $comment->content];
                $comments = $this->commentService->fetch($commentIds);
                /** @var Comment $c */
                foreach ($comments as $c) {
                    $contents[] = [$c->id, $c->content];
                }
                $this->process(AntiRubbish::TYPE_COMMENT, $comment, $contents, $contentLength);
            }
        }
    }

    private function isNumbers($content) {
        return preg_match('/\d{9,11}/mi', $content);
    }

    private function process($type, $target, $contents, $contentLength = 30) {
//        array_map(function($item) { printf("%s\t", $item[0]); }, $contents);
//        echo "\n";
	    if (count($contents) >= 3) {
		    $isRubbish = 0;
		    for ($i = 0; $i < 2; $i++) {
			    for ($j = $i + 1; $j < 3; $j++) {
				    $first = $contents[$i][1];
				    $second = $contents[$j][1];
				    $firstLength = strlen($first);
				    $secondLength = strlen($second);
				    if ($firstLength >= $contentLength && $secondLength >= $contentLength) {
				    	if ($firstLength >= 255 || $secondLength >= 255) {
						    similar_text($first, $second, $percent);
						    if ($percent > 0.7) {
							    $isRubbish += 1;
						    }
					    } else {
						    $maxLength = max($firstLength, $secondLength);
						    $score = levenshtein($first, $second) / $maxLength;
//						    printf("[1th]: %s\n[2nd]: %s\n[Score]: %f\n\n", $first, $second, $score);
						    if ($score <= 0.3) {
							    $isRubbish += 1;
						    }
					    }
				    }
			    }
		    }
		    if ($isRubbish >= 3) {
			    $this->setRubbish($type, $target->authorId, $target->id);
		    }
	    }
    }

    private function setRubbish($type, $authorId, $targetId) {
        $antiRubbish = new AntiRubbish();
        $antiRubbish->setType($type);
        $antiRubbish->setUserId($authorId);
        $antiRubbish->setTargetId($targetId);
        $em = $this->doctrine->getManager();
        $em->persist($antiRubbish);
        $em->flush();
    }

    private function isOfficialAccount($authorId) {
        return in_array($authorId, [
            31721
        ]);
    }
}