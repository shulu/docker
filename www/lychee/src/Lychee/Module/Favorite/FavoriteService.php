<?php
namespace Lychee\Module\Favorite;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Lychee\Bundle\CoreBundle\Entity\User;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Favorite\Entity\UserFavoritePost;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManagerInterface;

class FavoriteService {

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * FavoriteService constructor.
     * @param RegistryInterface $registry
     */
    public function __construct($registry) {
        $this->em = $registry->getManager();
    }

    /**
     * @param int $userId
     * @param int $postId
     */
    public function userAddFavoritePost($userId, $postId) {
        try {
            $r = new UserFavoritePost();
            $r->userId = $userId;
            $r->postId = $postId;
            $r->time = new \DateTime();
            $r->position = round(microtime(true) * 1000);
            $this->em->persist($r);
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            //do nothing;
        }
    }

    /**
     * @param int $userId
     * @param int $postId
     * @param bool
     */
    public function userRemoveFavoritePost($userId, $postId) {
        $dql = 'DELETE FROM '.UserFavoritePost::class.' f WHERE f.userId = :userId AND f.postId = :postId';
        $this->em->createQuery($dql)->execute(['userId' => $userId, 'postId' => $postId]);
    }

    public function userHasFavoritedPost($userId, $postId) {
        $dql = 'SELECT 1 FROM '.UserFavoritePost::class.' f WHERE f.userId = :userId AND f.postId = :postId';
        $r = $this->em->createQuery($dql)->execute(['userId' => $userId, 'postId' => $postId]);
        if (count($r) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int $userId
     * @param int[] $cursor
     * @param int $count
     * @param int[] $nextCursor
     * @return int[]
     */
    public function userListFavoritePost($userId, $cursor, $count, &$nextCursor) {
        if ($count == 0) {
            $nextCursor = $cursor;
            return array();
        }

        if (!is_array($cursor)) {
            $cursor = [PHP_INT_MAX, PHP_INT_MAX];
        }
        $position = (!isset($cursor[0]) || $cursor[0] == 0) ? PHP_INT_MAX : $cursor[0];
        $postId = (!isset($cursor[1]) || $cursor[1] == 0) ? PHP_INT_MAX : $cursor[1];

        $dql = 'SELECT f.postId, f.position FROM '.UserFavoritePost::class.' f WHERE f.userId = :userId'
            .' AND ((f.position = :position AND f.postId < :postId) OR f.position < :position)'
            .' ORDER BY f.position DESC, f.postId DESC';
        $query = $this->em->createQuery($dql);
        $query->setMaxResults($count);
        $query->setParameters(['userId' => $userId, 'position' => $position, 'postId' => $postId]);
        $rows = $query->getArrayResult();

        if (count($rows) < $count) {
            $nextCursor = [0, 0];
        } else {
            $lastRow = $rows[count($rows) - 1];
            $nextCursor = [$lastRow['position'], $lastRow['postId']];
        }

        return ArrayUtility::columns($rows, 'postId');
    }

    /**
     * @param int $userId
     * @param int[] $postIds
     * @return ParticalFavoritePostResolver
     */
    public function userBuildPostFavoriteResolver($userId, $postIds) {
        if ($userId == 0) {
            return new ParticalFavoritePostResolver(array());
        }

        $dql = 'SELECT f.postId FROM '.UserFavoritePost::class.' f WHERE f.userId = :userId AND f.postId IN (:postIds)';
        $query = $this->em->createQuery($dql);
        $result = $query->execute(['userId' => $userId, 'postIds' => $postIds]);
        $ids = ArrayUtility::columns($result, 'postId');

        $map = array_fill_keys($ids, true);
        return new ParticalFavoritePostResolver($map);
    }

    /**
     * @param $userId
     */
    public function getCount($userId) {
        $userId = intval($userId);
        if ($userId<=0) {
            return 0;
        }

        $q = $this->em->createQueryBuilder()
            ->select('count(1)')
            ->from(UserFavoritePost::class, 'f')
            ->where('f.userId=?1')
            ->setParameter(1, $userId)
            ->getQuery();

        $result = $q->getSingleScalarResult();
        $result = intval($result);
        return $result;
    }

}