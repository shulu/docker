<?php

namespace Lychee\Module\Like\Tests;

use Lychee\Component\Test\ModuleAwareTestCase;

/**
 * @group \Lychee\Module\Like\LikeService
 */
class LikeTest extends ModuleAwareTestCase {

    /**
     * 验证评论点赞
     *
     * @covers ::likeComment
     *
     */
    public function testLikeComment() {
        $uid = 6;
        $postId = 125357892431873;
        $comment = $this->comment()->create(
            $postId, $uid, '测试评论 '.date('YmdHis'),
            null, '','127.0.0.1', '', ''
        );
        $commentId = $comment->id;
        $this->like()->likeComment($uid, $commentId, $likedBefore);

        $r = $this->comment()->fetch([$comment->id]);
        $r = reset($r);
        $this->assertEquals(1, $r->likedCount-$comment->likedCount);
    }

    /**
     * 验证取消评论点赞
     *
     * @covers ::cancelLikeComment
     *
     */
    public function testCancelLikeComment() {
        $uid = 1;
        $postId = 125357892431873;
        $comment = $this->comment()->create(
            $postId, $uid, '测试评论 '.date('YmdHis'),
            null, '','127.0.0.1', '', ''
        );
        $commentId = $comment->id;
        $this->like()->likeComment($uid, $commentId, $likedBefore);
        $this->like()->cancelLikeComment($uid, $commentId);

        $r = $this->comment()->fetch([$comment->id]);
        $r = reset($r);
        $this->assertEquals(0, $r->likedCount-$comment->likedCount);
    }

}
