<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 6/11/15
 * Time: 2:47 PM
 */

namespace Lychee\Module\Recommendation\Task;

use Doctrine\DBAL\Connection;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Task\Task;
use Lychee\Module\Recommendation\Entity\RecommendationCronJob;
use Lychee\Module\Recommendation\Entity\RecommendationItem;
use Lychee\Module\Recommendation\RecommendationType;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Lychee\Component\Foundation\ArrayUtility;

/**
 * Class PublishRecommendationTask
 * @package Lychee\Bundle\AdminBundle\Task
 */
class PublishRecommendationTask implements Task {
    use ContainerAwareTrait;
    use ModuleAwareTrait;

    /**
     * @return string
     */
    public function getName() {
        return 'PublishRecommendation';
    }

    /**
     *
     */
    public function run() {
        $end = new \DateTime();
        /**
         * @var $em \Doctrine\ORM\EntityManager
         */
        $em = $this->container()->get('doctrine')->getManager();
        $query = $em->getRepository(RecommendationCronJob::class)->createQueryBuilder('rcj')
            ->where('rcj.publishTime < :end')
            ->setParameter('end', $end)
            ->orderBy('rcj.id', 'ASC')
            ->getQuery();
        $result = $query->getResult();
        if (null !== $result) {
            $postIds = array();
            /**
             * @var $row \Lychee\Module\Recommendation\Entity\RecommendationCronJob
             */
            foreach ($result as $row) {
                $item = new RecommendationItem();
                $item->setType($row->getRecommendationType())
                    ->setTargetId($row->getRecommendationId())
                    ->setImage($row->getImage())
                    ->setReason($row->getRecommendedReason());
                $annotation = json_decode($row->getAnnotation(), true);
                if ($annotation) {
                    if ($row->getRecommendationType() === RecommendationType::POST) {
                        if ($annotation['sticky_post']) {
                            $item->setSticky(1);
                        }
                    }
                }
                $em->persist($item);
                $em->flush();

                $em->remove($row);
                if ($row->getRecommendationType() == 'post') {
                    $postIds[] = $row->getRecommendationId();
                }
            }
            $em->flush();
            $this->container()->get('memcache.default')->delete('rec_web');
            $this->container()->get('memcache.default')->delete('rec_web2');

            if (count($postIds) > 0) {
                $settleProcessor = $this->container()->get('lychee.module.recommendation.group_posts_settle_processor');
                $groupPostsService = $this->container()->get('lychee.module.recommendation.group_posts');
                $result = $settleProcessor->process($postIds);
                foreach ($result->getIterator() as $groupId => $groupPostIds) {
                    $groupPostsService->addPostIdsToGroup($groupId, $groupPostIds, true);
                }
            }
        }
    }

    /**
     * @return int
     */
    public function getDefaultInterval() {
        return 300;
    }
}