<?php
/**
 * Created by PhpStorm.
 * User: ys160726
 * Date: 2016/12/8
 * Time: 下午4:36
 */

namespace Lychee\Module\Game;

use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Game\Entity\GameColumnsRecommendation;
use Symfony\Bridge\Doctrine\RegistryInterface;
class GameColumnsRecommendationManager
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    public function __construct(RegistryInterface $doctrineRegistry)
    {
        $this->entityManager = $doctrineRegistry->getManager();
    }

    /**
     * 通过栏目ID获取对应栏目的推荐游戏
     * @param $id
     * @return array
     */
    public function getGamesByColumnId($id) {
        $games = $this->entityManager->getRepository(GameColumnsRecommendation::class)
            ->createQueryBuilder('gcr')
            ->select('gcr.gameId, gcr.position')
            ->where('gcr.columnId = :columnId')
            ->orderBy('gcr.position', 'ASC')
            ->setParameter('columnId', $id)
            ->getQuery()
            ->getResult();
        return ArrayUtility::mapByColumn($games, 'gameId');
    }

    /**
     * 获取最大的位置信息
     * @return mixed
     */
    public function getMaxPosition() {
        $query = $this->entityManager->createQuery(
            'select max(gcr.id) maxPosition from '.GameColumnsRecommendation::class.' gcr'
        );
        $maxPosition=$query->getResult();
        return $maxPosition[0]['maxPosition'];
    }

    /**
     * 通过游戏ID和栏目ID来获取推荐游戏
     * @param $columnId
     * @param $gameId
     * @return array
     */
    public function getRecommendationByGameAndColumn($columnId, $gameId) {
        $recommendaton = $this->entityManager->getRepository(GameColumnsRecommendation::class)
            ->createQueryBuilder('gcr')
            ->where('gcr.columnId = :columnId')
            ->andWhere('gcr.gameId = :gameId')
            ->setParameters(array('columnId'=>$columnId, 'gameId'=>$gameId))
            ->getQuery()
            ->getResult();
        return $recommendaton;
    }

    /**
     * 在某个栏目下添加对应栏目的推荐游戏
     * @param $gameId
     * @param $columnId
     * @return bool
     */
    public function addRecommendation($gameId, $columnId) {
        $record = $this->getRecommendationByGameAndColumn($columnId, $gameId);
        if ($record) {
            return false;
        }
        $recommendation = new GameColumnsRecommendation();
        $recommendation->columnId = $columnId;
        $recommendation->gameId = $gameId;
        $maxPosition = $this->getMaxPosition();
        $recommendation->position = $maxPosition + 1;
        $this->entityManager->persist($recommendation);
        $this->entityManager->flush();
        return true;
    }

    /**
     * 删除某个栏目下面的某个游戏推荐
     * @param $columnId
     * @param $gameId
     */
    public function deleteColumnRecommendationGame($columnId, $gameId) {
        $recommendaton = $this->getRecommendationByGameAndColumn($columnId,$gameId);
        if ($recommendaton) {
            $this->entityManager->remove($recommendaton[0]);
            $this->entityManager->flush();
        }
    }

    /**
     * 通过位置信息获取推荐游戏
     * @param $columnId
     * @param $position
     * @return null|object
     */
    public function getRecommendationByPosition($columnId, $position) {
        $recommendation = $this->entityManager->getRepository(GameColumnsRecommendation::class)
            ->findOneBy(['position' => $position, 'columnId' => $columnId]);
        return $recommendation;
    }

    /**
     * 交换两个游戏推荐的位置信息
     * @param $columnId
     * @param $current
     * @param $next
     */
    public function updateRecommendationPositions($columnId, $current, $next) {
        /** @var GameColumnsRecommendation::class $currentRecommendation */
        $currentRecommendation = $this->getRecommendationByPosition($columnId, $current);
        /** @var GameColumnsRecommendation::class $nextRecommendation */
        $nextRecommendation = $this->getRecommendationByPosition($columnId, $next);
        $currentRecommendation->position = $next;
        $nextRecommendation->position = $current;
        $this->entityManager->persist($currentRecommendation);
        $this->entityManager->persist($nextRecommendation);
        $this->entityManager->flush();
    }
}