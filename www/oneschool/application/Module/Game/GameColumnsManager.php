<?php
/**
 * Created by PhpStorm.
 * User: ys160726
 * Date: 2016/12/8
 * Time: 下午4:31
 */

namespace Lychee\Module\Game;

use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Game\Entity\Game;
use Lychee\Module\Game\Entity\GameColumns;
use Lychee\Module\Game\Entity\GameColumnsRecommendation;
use Symfony\Bridge\Doctrine\RegistryInterface;

class GameColumnsManager
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
     * 添加栏目
     * @param $title
     */
    public function addColumn($title) {
        $column = new GameColumns();
        $column->title = $title;
        $this->entityManager->persist($column);
        $this->entityManager->flush();
    }

    /**
     * 获取所有栏目
     * @return array
     */
    public function getColumns() {
        $columns = $this->entityManager->getRepository(GameColumns::class)->findAll();
        return $columns;
    }

    /**
     * 删除某个栏目以及该栏目下的所有推荐游戏
     * @param $id
     */
    public function deleteColumn($id) {
        $column = $this->fetchOneById($id);
        $gameRecommendations = $this->entityManager->getRepository(GameColumnsRecommendation::class)->findBy(['columnId' => $id]);
        if ($column) {
            $this->entityManager->remove($column);
            $this->entityManager->flush();
        }
        if ($gameRecommendations) {
            foreach ($gameRecommendations as $recommendation) {
                $this->entityManager->remove($recommendation);
                $this->entityManager->flush();
            }
        }
    }

    /**
     * 通过栏目ID获取栏目
     * @param $id
     * @return null|object
     */
    public function fetchOneById($id) {
        $column = $this->entityManager->getRepository(GameColumns::class)->find($id);
        return $column;
    }

    /**
     * 编辑栏目标题
     * @param $id
     * @param $title
     */
    public function editColumnTitle($id, $title) {
        /** @var GameColumns $column */
        $column = $this->entityManager->getRepository(GameColumns::class)->find($id);
        if ($column) {
            $column->title = $title;
            $this->entityManager->flush();
        }
    }
}