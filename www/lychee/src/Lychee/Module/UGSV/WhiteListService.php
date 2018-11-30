<?php
namespace Lychee\Module\UGSV;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\KVStorage\DoctrineStorage;
use Lychee\Module\UGSV\Entity\WhiteUser as WhiteUserEntity;
use Lychee\Component\Foundation\ImageUtility;

class WhiteListService {

    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var EntityManagerInterface
     */
    private $doctrine;
    /**
     * @var EntityManagerInterface
     */
    private $emName;

    /**
     * @var DoctrineStorage
     */
    private $entityStorage;

    /**
     * @param RegistryInterface $registry
     * @param string $emName
     */
    public function __construct($registry, $emName) {
        $this->doctrine = $registry;
        $this->emName = $emName;
    }

    public function getEntityManager() {
        if (empty($this->em)) {
            $this->em = $this->doctrine->getManager($this->emName);
        }
        return $this->em;
    }

    public function getEntityStorage() {
        if (empty($this->entityStorage)) {
            $em = $this->getEntityManager();
            $this->entityStorage = new DoctrineStorage($em, WhiteUserEntity::class);
        }
        return $this->entityStorage;
    }

    /**
     * @param array 用户id数组
     *
     * @return bool
     * @throws \Exception
     */
    public function create($userIds) {
        $conn = $this->getEntityManager()->getConnection();
        $sql='INSERT IGNORE INTO ugsv_white_list(`user_id`, `create_time`)values';

        $i = 0;
        $sum = count($userIds);
        $values = [];
        foreach ($userIds as $userId) {
            $userId = intval($userId);
            if ($userId<1) {
                continue;
            }
            $values[] = '('.$userId.",'".date('Y-m-d H:i:s')."')";
            $i++;
            if (0!=$i%100
                &&$i<$sum) {
                continue;
            }
            $conn->executeQuery($sql.implode(',', $values));
            $values=[];
        }
        return true;
    }

    /**
     * @param int $id
     *
     * @return WhiteList|null
     */
    public function fetchOne($id) {
        $es = $this->getEntityStorage();
        return $es->get($id);
    }

    /**
     * @param array 用户id数组
     *
     * @return bool
     * @throws \Exception
     */
    public function remove($userId) {
        $user = $this->fetchOne($userId);
        if (empty($user)) {
            return false;
        }
        $em = $this->getEntityManager();
        $em->remove($user);
        $em->flush();
        return true;
    }

    /**
     * 按用户id降序迭代实体数据
     * @param EntityManager $entityManager
     * @param $repository
     * @param string $fieldName
     * @param string $order
     * @return QueryCursorableIterator
     */
    public function iterateForPager($keyword=null)
    {
        $em = $this->getEntityManager();

        $iterator = new \Lychee\Component\Foundation\CursorableIterator\CustomizedCursorableIterator(function($cursor, $step, &$nextCursor)use($em, $keyword){

            $sqlparams=[':cursor'=>$cursor];

            $sql = "SELECT 
                u.id as userId, 
                u.nickname as nickname, 
                w.create_time as createTime, 
                u.avatar_url as avatarUrl 
                FROM ugsv_white_list w
                INNER JOIN user u ON w.user_id = u.id
                WHERE w.user_id<:cursor ";

            if (!is_null($keyword)) {
                $sql .= " and (u.nickname like :keyword1 or u.id=:keyword2 ) ";
                $sqlparams[':keyword1'] = '%'.$keyword.'%';
                $sqlparams[':keyword2'] = $keyword;
            }

            $sql .= "ORDER BY w.user_id DESC LIMIT ".($step+1);
            $conn = $em->getConnection();
            $sth = $conn->prepare($sql);
            $sth->execute($sqlparams);
            $res = $sth->fetchAll(\PDO::FETCH_ASSOC);
            $nextCursor = 0;
            if (isset($res[$step])) {
                unset($res[$step]);       
                $last = end($res);
                $nextCursor = $last['userId'];     
            }
            foreach ($res as $key => $item){
                $item['avatarUrl'] = ImageUtility::formatUrl($item['avatarUrl']);
                $res[$key] = $item;
            }
            return $res;
        });

        return $iterator;
    }


    /**
     * 判断是否存在白名单
     * @param int $id
     *
     * @return int 1|0
     */
    public  function  isExist($userId)
    {
        $r = $this->fetchOne($userId);
        if ($r) {
            return 1;
        }
        return 0;
    }

}