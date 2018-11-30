<?php
namespace Lychee\Module\UGSV;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\UGSV\Entity\BGM as BGMEntity;
use Lychee\Component\KVStorage\DoctrineStorage;
use Lychee\Component\Foundation\ImageUtility;

class BGMService {

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
            $this->entityStorage = new DoctrineStorage($em, BGMEntity::class);
        }
        return $this->entityStorage;
    }

    /**
     * @param BGMParameter $parameter
     *
     * @return BGM
     * @throws \Exception
     */
    public function create($parameter) {

        $bgm = new BGMEntity();
        $bgm->name = $parameter->name;
        $bgm->singerName = $parameter->singerName;
        $bgm->size = $parameter->size;
        $bgm->duration = $parameter->duration;
        $bgm->cover = $parameter->cover;
        $bgm->src = $parameter->src;
        $bgm->updateTime = $bgm->createTime = new \DateTime();

        $em = $this->getEntityManager();
        $em->persist($bgm);
        $em->flush();

        $this->fixWeights(100);

        return $bgm;
    }

    /**
     * 矫正权重，为了避免出现不同的记录拥有相同的权重，将导致上下交换的权重的功能失效
     *
     * @param int 矫正的记录数，0即不限
     * @return  bool
     * @throws \Exception
     */
    public function fixWeights($limit=0) {
        $em = $this->getEntityManager();
        $query = $em->createQuery('
            SELECT max(t.id)
            FROM '.BGMEntity::class.' t
        ');
        $weightCursor = $query->getOneOrNullResult();
        if (empty($weightCursor)) {
            return false;
        }
        $weightCursor = reset($weightCursor);
        $weightCursor = intval($weightCursor);
        $step = 100;
        $offset = 0;
        do {

            list($list, $offset) = $this->getHotList($offset, $step);
            if (empty($list)) {
                return true;
            }

            foreach ($list as $key => $item) {
                $item->weight=$weightCursor;
                $weightCursor--;
                $list[$key] = $item;
            }
            $em->flush();

            if ($offset<=0
                ||($limit&&$offset>=$limit)) {
                return true;
            }

        } while (1);
        return true;
    }


    /**
     * @param BGMParameter $parameter
     *
     * @return BGM
     * @throws \Exception
     */
    public function update($parameter) {
        $bgm = $this->fetchOne($parameter->id);
        if (empty($bgm)) {
            return false;
        }
        if ($parameter->name) {
            $bgm->name = $parameter->name;
        }
        if ($parameter->singerName) {
            $bgm->singerName = $parameter->singerName;
        }
        if ($parameter->duration) {
            $bgm->duration = $parameter->duration;
        }
        if ($parameter->cover) {
            $bgm->cover = $parameter->cover;
        }
        if ($parameter->src) {
            $bgm->src = $parameter->src;
            $bgm->size = $parameter->size;
        }
        $bgm->updateTime = new \DateTime();
        $es = $this->getEntityStorage();
        $es->set($bgm->id, $bgm);
        return $bgm;
    }

    /**
     * 按热门顺序迭代实体数据
     * @param EntityManager $entityManager
     * @param $repository
     * @param string $fieldName
     * @param string $order
     * @return QueryCursorableIterator
     */
    public function iterateForPager($keyword=null)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository(BGMEntity::class);

        $iterator = new \Lychee\Component\Foundation\CursorableIterator\CustomizedCursorableIterator(function($cursor, $step, &$nextCursor)use($repo, $keyword){

            $qb = $repo->createQueryBuilder('repo');

            if (!is_null($keyword)) {
                $qb->where('repo.name LIKE :keyword1 or repo.singerName LIKE :keyword1 or repo.id = :keyword2');
                $qb->setParameter('keyword1', '%'.$keyword.'%');
                $qb->setParameter('keyword2', $keyword);

            }

            $qb->orderBy("repo.weight", 'DESC')
            ->addOrderBy("repo.id", 'ASC')
            ->setFirstResult($cursor)
            ->setMaxResults($step+1);

            $query = $qb->getQuery();

            $res = $query->getResult();

            $nextCursor = 0;
            if (isset($res[$step])) {
                $nextCursor = $cursor + $step;
                unset($res[$step]);
            }

            foreach ($res as $key => $item) {
                $item = $this->formatBGMFields($item);
                $res[$key] = $item;
            }

            return $res;
        });

        return $iterator;
    }


    public function formatBGMFields($item) {
        if (empty($item)) {
            return null;
        }
        if (isset($item->src)) {
            $item->src = ImageUtility::formatUrl($item->src);
        }
        if (isset($item->cover)) {
            $item->cover = ImageUtility::formatUrl($item->cover);
        }

        if (is_array($item)
            &&isset($item['src'])) {
            $item['src'] = ImageUtility::formatUrl($item['src']);
        }

        if (is_array($item)
            &&isset($item['cover'])) {
            $item['cover'] = ImageUtility::formatUrl($item['cover']);
        }

        return $item;
    }


    /**
     * @param int 分页偏移量
     * @param int 限制查询记录数
     *
     * @return  array  
     *          array[0]  array<BGM>   背景音乐列表
     *          array[1]  int          下一页的开始偏移量, 0即没有下一页了
     */
    public function getHotList($cursor, $limit) {
        $em = $this->getEntityManager();
        $repo = $em->getRepository(BGMEntity::class);
        $qb = $repo->createQueryBuilder('repo')
            ->orderBy("repo.weight", 'DESC')
            ->addOrderBy("repo.id", 'ASC')
            ->setFirstResult($cursor)
            ->setMaxResults($limit+1);
        $query = $qb->getQuery();
        $res = $query->getResult();

        $nextCursor = 0;
        if (isset($res[$limit])) {
            $nextCursor = $cursor + $limit;
            unset($res[$limit]);

        }

        foreach ($res as $key => $val) {
            $this->formatBGMFields($val);
        }
        return array($res, $nextCursor);
    }

    /**
     * @param int $id
     *
     * @return BGM|null
     */
    public function fetchOne($id) {
        $es = $this->getEntityStorage();
        $item =  $es->get($id);
        $item = $this->formatBGMFields($item);
        return $item;
    }


    public function fetchByKeyword($keyword, $offset, $limit) {
        if ($limit === 0) {
            return array();
        }
        $em = $this->getEntityManager();
        $query = $em->createQuery('
            SELECT t
            FROM '.BGMEntity::class.' t
            WHERE t.name LIKE :keyword or t.singerName LIKE :keyword or t.id= :keyword
            ORDER BY t.weight DESC, t.id DESC
        ')
        ->setFirstResult($offset)
        ->setMaxResults($limit);
        $res = $query->execute(array('keyword' => '%'. $keyword . '%'));

        return ArrayUtility::mapByColumn($res, 'id');
    }

    /**
     * @param int bgmId
     *
     * @return bool
     * @throws \Exception
     */
    public function remove($bgmId) {
        $bgm = $this->fetchOne($bgmId);
        if (empty($bgm)) {
            return false;
        }
        $em = $this->getEntityManager();
        $em->remove($bgm);
        $em->flush();
        return true;
    }

    /**
     * 获取当前最大权重值
     * @param int bgmId
     *
     * @return int
     * @throws \Exception
     */
    public function getCurrMaxWeight() {
        $em = $this->getEntityManager();
        $query = $em->createQuery('
            SELECT max(t.weight)
            FROM '.BGMEntity::class.' t
        ');
        $res = $query->getOneOrNullResult();
        if (empty($res)) {
            return 0;
        }
        $res = reset($res);
        $res = intval($res);
        return $res;
    }

    /**
     * 置顶
     * @param int $id   背景音乐id
     *
     * @return bool
     * @throws \Exception
     */
    public function topWeight($id) {
        $bgm = $this->fetchOne($id);
        if (empty($bgm)) {
            return false;
        }
        $maxWeight = $this->getCurrMaxWeight();
        $bgm->weight=time();
        $diff = $maxWeight-$bgm->weight;
        if ($diff>=0) {
            $bgm->weight = $bgm->weight+$diff+1;
        }
        $bgm->updateTime = new \DateTime();
        $es = $this->getEntityStorage();
        $es->set($bgm->id, $bgm);
        return true;
    }


    /**
     * 集体提升权重，是置底元素的前置动作
     *
     * @return bool
     * @throws \Exception
     */
    public function upWeights() {
        $em = $this->getEntityManager();
        $em->createQuery('update '.BGMEntity::class.' t 
            set t.weight=t.weight+1')
        ->execute();
    }

    /**
     * 置底
     * @param int  背景音乐id
     *
     * @return bool
     * @throws \Exception
     */
    public function bottomWeight($id) {
        $bgm = $this->fetchOne($id);
        if (empty($bgm)) {
            return false;
        }
        $this->upWeights();
        $em = $this->getEntityManager();
        $em->createQuery('update '.BGMEntity::class.' t 
            set t.weight = 0,
            t.updateTime = :time
            where t.id = :id')
        ->execute(array('id'=>$id, 'time'=>new \DateTime()));

        return true;
    }

    /**
     * 交换权重，排序位将对调
     * @param int    背景音乐id
     * @param int    背景音乐id
     *
     * @return bool
     * @throws \Exception
     */
    public function switchWeight($id1, $id2) {
        $em = $this->getEntityManager();

        $res = $em->getRepository(BGMEntity::class)
            ->findBy(array('id'=>[$id1, $id2]));
        if (empty($res) || count($res)<2) {
            return false;
        }

        list($bgm1, $bgm2) =  $res;

        $swap = $bgm1->weight;
        $bgm1->weight = $bgm2->weight;
        $bgm2->weight = $swap;

        $es = $this->getEntityStorage();
        $es->set($bgm1->id, $bgm1);
        $es->set($bgm2->id, $bgm2);
        return true;
    }


    /**
     * 累计使用次数
     * @param int    背景音乐id
     *
     * @return bool
     * @throws \Exception
     */
    public function incUseCount($bgmId, $delta) {
        $em = $this->getEntityManager();
        $query = $em->createQuery('
            UPDATE '.BGMEntity::class.' t
            SET t.useCount = t.useCount + :delta
            WHERE t.id = :bgmId
        ')->setParameters(array('bgmId' => $bgmId, 'delta' => $delta));
        $query->execute();
        return true;
    }

    /**
     * 统计背景音乐的使用次数
     * @param int    背景音乐id
     *
     * @return bool
     * @throws \Exception
     */
    public function fetchUseCountings($bgmIds) {
        if (empty($bgmIds)) {
            return [];
        }

        $sql = 'SELECT bgm_id, count(post_id) n 
        FROM ugsv_post
        WHERE bgm_id in ('.implode(',', array_fill(0, count($bgmIds), '?')).')
        GROUP BY bgm_id';
        $em = $this->getEntityManager();
        $stat = $em->getConnection()->executeQuery($sql, $bgmIds);
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($rows)) {
            return [];
        }
        $return = [];
        foreach ($rows as $item) {
            $return[$item['bgm_id']]=$item['n'];
        }
        return $return;
    }


}