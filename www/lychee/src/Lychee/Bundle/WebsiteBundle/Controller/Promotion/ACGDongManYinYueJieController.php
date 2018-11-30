<?php
namespace Lychee\Bundle\WebsiteBundle\Controller\Promotion;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class ACGDongManYinYueJieController extends Controller {
    /**
     * @Route("/acg_dongmanyinyuejie")
     * @Template("LycheeWebsiteBundle:Promotion:acg_dongman_yinyuejie.html.twig")
     */
    public function indexAction() {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }
        $topic = $this->topic()->fetchOne(30532);
        if ($topic == null) {
            throw $this->createNotFoundException();
        }

        $followerCount = $this->topicFollowing()->getTopicsFollowerCounter(array($topic->id))
            ->getCount($topic->id);

        $postIds = $this->post()->fetchStickyPostIds($topic->id);
        $postIds = array_slice($postIds, 0, 10);
        $remainCount = 10 - count($postIds);
        if ($remainCount > 0) {
            $sourcePostIds = $this->post()->fetchIdsByTopicId(
                $topic->id, 0, $remainCount, $nextCursor
            );
            $postIds = array_merge($postIds, $sourcePostIds);
            $postIds = array_unique($postIds);
        }
        $posts = $this->post()->fetch($postIds);

        return array(
            'topic' => $topic,
            'followerCount' => $followerCount,
            'posts' => $posts,
        );

        return array(

        );
    }
}