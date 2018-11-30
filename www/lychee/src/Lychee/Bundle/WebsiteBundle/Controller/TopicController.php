<?php
namespace Lychee\Bundle\WebsiteBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Lychee\Bundle\CoreBundle\Controller\Controller;

/**
 * @Route("/topic")
 */
class TopicController extends Controller {

    /**
     * @Route("/invite/{topicId}", name="topic_invite")
     */
    public function invite($topicId, Request $request) {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }
        $topic = $this->topic()->fetchOne(intval($topicId));
        if ($topic == null) {
            throw $this->createNotFoundException();
        }
        if ($topic->private || $topic->hidden) {
            return $this->renderPrivateTopic($topic);
        } else {
            return $this->renderPublicTopic($topic);
        }
    }

    private function renderPrivateTopic($topic) {
        $manager = $this->account()->fetchOne($topic->managerId);

        $followerCount = $this->topicFollowing()->getTopicsFollowerCounter(array($topic->id))
            ->getCount($topic->id);

        $newFollowerIds = $this->topicFollowing()->getTopicFollowerIterator($topic->id)
            ->setStep(7)->setCursor(0)->current();

        $followers = $this->account()->fetch($newFollowerIds);

        $data = array(
            'topic' => $topic,
            'manager' => $manager,
            'followerCount' => $followerCount >= 100000 ? floor($followerCount / 10000) . '万' : $followerCount,
            'followers' => $followers,
        );
        return $this->render('LycheeWebsiteBundle:Topic:invite_private.html.twig', $data);
    }

    private function renderPublicTopic($topic) {
        $followerCount = $this->topicFollowing()->getTopicsFollowerCounter(array($topic->id))
            ->getCount($topic->id);

        $postIds = $this->post()->fetchIdsByTopicId($topic->id, 0, 5);
        $posts = $this->container->get('lychee_api.synthesizer_builder')
            ->buildBasicPostSynthesizer($postIds, 0)->synthesizeAll();

        $data = array(
            'topic' => $topic,
            'followerCount' => $followerCount >= 100000 ? floor($followerCount / 10000) . '万' : $followerCount,
            'posts' => $posts,
        );
        return $this->render('LycheeWebsiteBundle:Topic:invite_public.html.twig', $data);
    }

    /**
     * @Route("/shortcut/{topicId}")
     */
    public function topicShortcut($topicId = null) {
        $topicId = intval($topicId);
        if ($topicId == 0) {
            throw $this->createNotFoundException();
        }
        $topic = $this->topic()->fetchOne($topicId);
        return $this->render('LycheeWebsiteBundle:Topic:shortcut.html.twig', array('topic' => $topic));
    }

    private function getPostsData($postIds) {

    }

}