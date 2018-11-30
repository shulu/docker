<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 15-2-4
 * Time: 下午2:28
 */

namespace Lychee\Module\Recommendation\Task;


use Doctrine\ORM\EntityManager;
use Lychee\Component\Task\Task;
use Lychee\Module\Post\PostAnnotation;
use Lychee\Module\Post\PostParameter;
use Lychee\Module\Post\PostService;
use Lychee\Module\Recommendation\Entity\CapturedSiteContent;
use Lychee\Module\Topic\Following\TopicFollowingService;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class CaptureSiteContentTask
 * @package Lychee\Module\Recommendation\Task
 */
class CaptureSiteContentTask implements Task {

    protected $obs = [];

    use ContainerAwareTrait;

    /**
     * @return string
     */
    public function getName() {
        return 'capture-site-content';
    }

    /**
     * Per hour
     * @return int
     */
    public function getDefaultInterval() {
        return 3600;
    }

    public function attach($observers) {
        foreach ($observers as $observer) {
            $this->obs[] = $observer;
        }
    }

    public function run() {
        foreach ($this->obs as $ob) {
            /** @var Observer $ob */
            $data = $ob->doActor();
            $ids = array_keys($data);
            $ids = $this->filter($ob->getName(), $ids);
            foreach ($ids as $id) {
                $this->post($data[$id]);
                printf("[%s] %s\n", $ob->getName(), $data[$id]['content']);
            }
            $this->markPosted($ob->getName(), $ids);
        }
    }

    private function filter($siteName, $ids) {
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine')->getManager();
        $result = $em->getRepository(CapturedSiteContent::class)->findBy([
            'siteName' => $siteName,
            'contentId' => $ids,
        ]);
        if ($result) {
            return array_diff($ids, array_map(function($item) {
                /** @var CapturedSiteContent $item */
                return $item->getContentId();
            }, $result));
        }
        return $ids;
    }

    private function post($data) {
        $authorId = $data['authorId'];
        $topicId = $data['topicId'];
        /** @var TopicFollowingService $topicFollowingService */
        $topicFollowingService = $this->container->get('lychee.module.topic.following');
        if (false === $topicFollowingService->isFollowing($authorId, $topicId)) {
            $topicFollowingService->follow($authorId, $topicId);
        }
        /** @var PostService $postService */
        $postService = $this->container->get('lychee.module.post');
        $params = new PostParameter();
        $params->setAuthorId($authorId)
            ->setContent($data['content'])
            ->setTopicId($topicId)
            ->setType($data['type'])
            ->setResource(null, $data['videoUrl'])
            ->setAnnotation(json_encode(PostAnnotation::setVideoCover($data['thumbnail'])));
        $postService->create($params);
    }

    private function markPosted($siteName, $ids) {
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine')->getManager();
        foreach ($ids as $id) {
            $capturedSiteContent = new CapturedSiteContent();
            $capturedSiteContent->setSiteName($siteName);
            $capturedSiteContent->setContentId($id);
            $em->persist($capturedSiteContent);
        }
        $em->flush();
    }
}