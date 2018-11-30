<?php
/**
 * Created by PhpStorm.
 * User: mmfei
 * Date: 2018/8/4
 * Time: 下午7:14
 */
namespace Lychee\Module\CiyoActivity;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Module\Account\AccountService;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Lychee\Module\CiyoActivity\Entity\ActivityTopCiyoByWeek;
use Lychee\Module\CiyoActivity\Entity\ActivityTopCiyoByWeekVideos;
use Lychee\Module\Post\PostService;
/**
 * Class TopCiyoByWeekService
 * @package Lychee\Module\CiyoActivity
 */
class TopCiyoByWeekService {
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var PostService
     */
    private $postservice;
    /**
     * @var AccountService
     */
    private $accountService;


    /**
     * ExtraMessageService constructor.
     *
     * @param RegistryInterface $doctrine
     * @param PostService $postservice
     * @param AccountService $accountService
     */
    public function __construct(RegistryInterface $doctrine,PostService $postService,AccountService $accountService) {
        $this->em = $doctrine->getManager();
        $this->postservice = $postService;
        $this->accountService = $accountService;
    }
    public function getById($id){
       $result = $this->em->getRepository(ActivityTopCiyoByWeek::class)->findOneBy([
           "id" => $id
       ]);
       $a = [$result];
       $return = $this->_bindData($a);
       return array_pop($return);
    }
    public function getAll(){
        $result = $this->em->getRepository(ActivityTopCiyoByWeek::class)->findAll();
        return $this->_bindData($result);
    }
    public function getList($page = 1, $pageCount = 10){
        if($page < 1) $page = 1;
        if($pageCount < 1) $pageCount = 10;

        $result = $this->em->getRepository(ActivityTopCiyoByWeek::class)->findBy([],['id'=>'desc'],$pageCount,($page - 1)*$pageCount);
        return $this->_bindData($result);
    }
    private function _bindData(&$topCiyoList){
        $idList = [];
        foreach($topCiyoList as $k => $o)
        {
            $topCiyoList[$k]->videoList = [];
            $idList[] = $o->id;
        }
        //获取每周精选视频的视频id
        $videoList = $this->em->getRepository(ActivityTopCiyoByWeekVideos::class)->findBy(['activity_top_ciyo_by_week_id'=>$idList]);

        if (empty($videoList)) return $topCiyoList;

        $postIdList = $postList = $topCiyoVideoMapping = $reasonList = [];
        foreach($videoList as $v)
        {
            $postIdList[] = $v->video_id;
            $topCiyoVideoMapping[$v->activity_top_ciyo_by_week_id][] = $v->video_id;
            $reasonList[$v->video_id] = $v;
        }

        //获取帖子信息
        $postDataList = $this->postservice->fetch($postIdList);
        if (empty($postDataList)) return $topCiyoList;

        $authorIdList = [];
        foreach($postDataList as $p){
            $postList[$p->id] = $p;
            $authorIdList[$p->authorId] = $p->id;
        }

        $userDataList = $this->accountService->fetch(array_keys($authorIdList));
        $userDataList_ = [];
        foreach($userDataList as $user){
            $userDataList_[$user->id] = $user->nickname;
        }
        foreach($postList as $postId => $postObject){
            $postList[$postId]->author = (object) ['nickname' => $userDataList_[$postObject->authorId]];
        }

        //把帖子信息【图片，id】附带到每周精选视频
        foreach($topCiyoList as $k =>$topCiyo){
            $_postDataList = [];
            if(isset($topCiyoVideoMapping[$topCiyo->id])){
                foreach($topCiyoVideoMapping[$topCiyo->id] as $videoId){
                    $a = $postList[$videoId];
                    $a->reasonText = $reasonList[$videoId]->reason_text;
                    $_postDataList[] = $a;
                }
            }

            $topCiyoList[$k]->videoList = $_postDataList;
        }

        return $topCiyoList;
    }
    public function create($id,$name, $start , $end, array $videoAndReasonList){
        if ($name == ''){
            return false;
        }
        if (empty($videoAndReasonList)){
            return false;
        }
        if(!is_numeric($start)) return false;
        if(!is_numeric($end)) return false;
        $id = (int)$id;


        $topCiyoByWeek = new ActivityTopCiyoByWeek();
        if($id > 0)
            $topCiyoByWeek->id = $id;
        $topCiyoByWeek->name = $name;
        $topCiyoByWeek->start_timestamp = $start;
        $topCiyoByWeek->end_timestamp = $end;


        $this->em->persist($topCiyoByWeek);
        $this->em->flush();

        $id = $topCiyoByWeek->id;
        if($id < 1){
            return false;
        }

        foreach($videoAndReasonList as $videoId => $reason_text){
            $o = new ActivityTopCiyoByWeekVideos();
            $o->video_id = $videoId;
            $o->activity_top_ciyo_by_week_id = $id;
            $o->reason_text = $reason_text;

            $this->em->persist($o);
            $this->em->flush();
        }

        return $id;
    }
    public function deleteByTopCiyoId($id){
        $id = (int)$id;
        if($id < 1) return false;

        $topCiyoByWeek = $this->getById($id);
        if(empty($topCiyoByWeek)) return true;

        $result = $this->em->getRepository(ActivityTopCiyoByWeekVideos::class)->findBy([
            "activity_top_ciyo_by_week_id" => $id
        ]);
        if($result){
            foreach($result as $o1){
//                $o = new ActivityTopCiyoByWeekVideos();
//                $o->video_id = $o1['video_id'];
//                $o->activity_top_ciyo_by_week_id = $id;
//                $o->reason_text = $o1['reason_text'];

                $this->em->remove($o1);
            }
            $this->em->flush();
        }
        $this->em->remove($topCiyoByWeek);
        $this->em->flush();
        return true;
    }

    /**
     * getCurrentTopCiyoByWeek
     *
     *
     * 获取本周周榜内容。
     * @return ActivityTopCiyoByWeek|mixed|null|object
     */
    public function getCurrentTopCiyoByWeek(){
        //周日 00:00:00
//        $startTime = mktime(0, 0 , 0,date("m"),date("d")-date("w"),date("Y"));
        //下周日 00:00:00
        //$endTime = $startTime + 7 * 86400;
        //当前时间
        $curTime = time();

        $query = $this->em->getRepository(ActivityTopCiyoByWeek::class)
            ->createQueryBuilder('atc')
            ->where('atc.start_timestamp <= :curTime')
            ->andWhere('atc.end_timestamp > :curTime')
            ->setParameters(['curTime'=>$curTime])
            ->orderBy('atc.id','asc')
            ->getQuery();
        $result = $query->getResult();

        if(empty($result)) return [];

        $topCiyoList = [array_pop($result)];
        $topCiyoList = $this->_bindData($topCiyoList);
        $topCiyo = array_pop($topCiyoList);
        return $topCiyo;
    }
}