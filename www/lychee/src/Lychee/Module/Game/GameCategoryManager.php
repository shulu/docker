<?php
/**
 * Created by PhpStorm.
 * User: ys160726
 * Date: 2016/12/7
 * Time: 上午10:22
 */

namespace Lychee\Module\Game;

use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Game\Entity\Game;
use Lychee\Module\Game\Entity\GameCategory;
use Symfony\Bridge\Doctrine\RegistryInterface;

class GameCategoryManager
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
     * 添加分类
     * @param $name
     * @param $icon
     */
    public function addCategory($name, $icon) {
        $category = new GameCategory();
        $category->name = $name;
        $category->icon = $icon;
        $this->entityManager->persist($category);
        $this->entityManager->flush();
    }

    /**
     * 获取所有的游戏分类
     * @return array
     */
    public function getCategories() {
        $categories = $this->entityManager->getRepository(GameCategory::class)->findAll();
        return $categories;
    }

    /**
     * 删除某个分类以及该分类下的所有游戏
     * @param $id
     */
    public function deleteCategory($id) {
        $category = $this->entityManager->getRepository(GameCategory::class)->find($id);
        if ($category) {
            $this->entityManager->remove($category);
            $this->entityManager->flush();
            $games = $this->entityManager->getRepository(Game::class)->findBy(['categoryId' => $id]);
            if ($games) {
                foreach ($games as $game) {
                    $this->entityManager->remove($game);
                    $this->entityManager->flush();
                }
            }
        }
    }

    /**
     * 通过分类ID获取分类
     * @param $id
     * @return null|object
     */
    public function fetchOneById($id) {
        $category = $this->entityManager->getRepository(GameCategory::class)->find($id);
        return $category;
    }

    /**
     * 修改分类的名字，icon以及该分类下的游戏的appType。
     * @param $categoryId
     * @param $categoryName
     * @param $icon
     */
    public function editCategory($categoryId, $categoryName, $icon) {
        /** @var GameCategory $category */
        $category = $this->fetchOneById($categoryId);
        if ($category) {
            $category->name = $categoryName;
            if ($icon) {
                $category->icon = $icon;
            }
            $this->entityManager->persist($category);
            $this->entityManager->flush();
            $games = $this->entityManager->getRepository(Game::class)->findBy(['categoryId' => $categoryId]);
            if ($games) {
                /** @var Game $game */
                foreach ($games as $game) {
                    $game = $game->setAppType($categoryName);
                    $this->entityManager->persist($game);
                    $this->entityManager->flush();
                }
            }
        }
    }
}