<?php
namespace Lychee\Module\Game;

use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Game\Entity\Game;
use Lychee\Module\Game\Entity\GameCategory;
use Lychee\Module\Game\Entity\GameColumns;
use Lychee\Module\Game\Entity\GameColumnsRecommendation;
use Symfony\Bridge\Doctrine\RegistryInterface;

class GameManager {

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    public function __construct(RegistryInterface $doctrineRegistry) {
        $this->entityManager = $doctrineRegistry->getManager();
    }

    /**
     * @param $ids
     * @return array
     */
    public function fetch($ids) {
        $AppRepo = $this->entityManager->getRepository(Game::class);
        $query = $AppRepo->createQueryBuilder('a')
            ->where('a.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery();
        $result = $query->getResult();
        $sortResult = [];
        if (null !== $result) {
            $result = ArrayUtility::mapByColumn($result, 'id');
            foreach ($ids as $id) {
                if (isset($result[$id])) {
                    $sortResult[$id] = $result[$id];
                }
            }
        }

        return $sortResult;
    }

    public function fetchOne($id) {
        $result = $this->fetch([$id]);
        if (null !== $result && isset($result[$id])) {
            $result = $result[$id];
        }

        return $result;
    }

    public function fetchByCursor($cursor, $count, &$nextCursor = null) {
        if (0 == $count) {
            return [];
        }
        if (0 == $cursor) {
            $cursor = PHP_INT_MAX;
        }
        $query = $this->entityManager->createQuery('SELECT a FROM '.Game::class.' a WHERE a.id < :cursor ORDER BY a.id DESC');
        $query->setMaxResults($count)->setParameters([
            'cursor' => $cursor
        ]);
        $result = $query->getResult();
        if (count($result) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $result[count($result) - 1]->getId();
        }

        return ArrayUtility::mapByColumn($result, 'id');
    }

    /**
     * @param string $platform android or ios
     * @param int $cursor
     * @param int $count
     * @param int $nextCursor
     */
    public function fetchByPlatform($platform, $cursor, $count, &$nextCursor = null) {
        $platformField = $platform == 'ios' ? 'iosLink' : 'androidLink';
        if (0 == $count) {
            return [];
        }
        if (0 == $cursor) {
            $cursor = PHP_INT_MAX;
        }

        $query = $this->entityManager->createQuery('SELECT a FROM '.Game::class.' a WHERE a.id < :cursor AND a.'.$platformField.' IS NOT NULL AND LENGTH(a.'.$platformField.') > 0  ORDER BY a.id DESC');
        $query->setMaxResults($count)->setParameters([
            'cursor' => $cursor
        ]);
        $result = $query->getResult();
        if (count($result) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $result[count($result) - 1]->getId();
        }

        return ArrayUtility::mapByColumn($result, 'id');
    }

    public function delete($app) {
        $this->entityManager->remove($app);
        $this->entityManager->flush();
    }

	/**
	 * @param $columnId
	 *
	 * @return null|GameColumns
	 */
    public function fetchGameColumn($columnId) {
    	return $this->entityManager->getRepository(GameColumns::class)->find($columnId);
    }

	/**
	 * 获取游戏栏目推荐的游戏ID
	 *
	 * @param $columnId
	 * @param $platform
	 * @param $cursor
	 * @param $count
	 * @param null $nextCursor
	 *
	 * @return array
	 */
    public function fetchColumnRecommendationList($columnId, $platform, $cursor, $count, &$nextCursor = null) {
	    if ($platform === 'ios') {
		    $field = 'ios_link';
	    } else {
		    $field = 'android_link';
	    }
	    $conn = $this->entityManager->getConnection();
	    $sql = 'SELECT r.game_id, r.position FROM game_columns_recommendation r
				JOIN game g ON g.id=r.game_id
				WHERE r.column_id=' . $conn->quote($columnId) . ' AND r.position>' . $conn->quote($cursor) .
	           " AND g.$field IS NOT NULL AND g.$field != ''
	           ORDER BY r.position ASC
	           LIMIT $count";
	    $stmt = $conn->query($sql);
	    $result = $stmt->fetchAll();

	    if (is_array($result) && count($result) > 0) {
		    $nextCursor = $result[count($result) - 1]['position'];
		    $gameIds = ArrayUtility::columns($result, 'game_id');
	    } else {
		    $nextCursor = 0;
		    $gameIds = [];
	    }

	    return $gameIds;
    }

	/**
	 * 获取所有游戏分类
	 *
	 * @return array
	 */
    public function fetchGameCategories() {
	    return $this->entityManager->getRepository(GameCategory::class)->findAll();
    }

	/**
	 * @param $categoryId
	 * @param $platform
	 * @param $cursor
	 * @param $count
	 * @param null $nextCursor
	 *
	 * @return array
	 */
    public function fetchGamesByCat($categoryId, $platform, $cursor, $count, &$nextCursor = null) {
    	if (0 == $cursor) {
    		$cursor = PHP_INT_MAX;
	    }
	    if ($platform === 'ios') {
	    	$field = 'iosLink';
	    } else {
		    $field = 'androidLink';
	    }
    	$query = $this->entityManager->getRepository(Game::class)->createQueryBuilder('g')
		    ->where("g.id<:cursor AND g.categoryId=:catId AND g.$field IS NOT NULL AND g.$field != ''")
		    ->orderBy('g.id', 'DESC')
		    ->setParameter('cursor', $cursor)
		    ->setParameter('catId', $categoryId)
		    ->setMaxResults($count)
		    ->getQuery();

	    $result = $query->getResult();
	    if ($result) {
	    	/** @var Game $end */
		    $end = end($result);
		    reset($result);
		    $nextCursor = $end->getId();
		    $result = ArrayUtility::mapByColumn($result, 'id');
	    } else {
	    	$nextCursor = 0;
		    $result = [];
	    }

    	return $result;
    }

	/**
	 * @param $gameId
	 * @param int $increment
	 */
	public function gamePlayerNumberIncrement($gameId, $increment = 1) {
    	$conn = $this->entityManager->getConnection();
    	$sql = 'UPDATE game SET player_numbers=player_numbers+' . (int)$increment .
	           ' WHERE id=' . $conn->quote($gameId);
	    $conn->exec($sql);
    }

}