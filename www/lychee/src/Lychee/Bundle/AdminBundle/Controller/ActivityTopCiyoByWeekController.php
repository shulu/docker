<?php
namespace Lychee\Bundle\AdminBundle\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class AdController
 * @package Lychee\Bundle\AdminBundle\Controller
 * @Route("/activitytopciyobyweek")
 */
class ActivityTopCiyoByWeekController extends BaseController{

    public function getTitle() {
        return '每周精选次元';
    }
    /**
     * @Route("/")
     * @Template
     * @param Request $request
     * @return array
     */
    public function indexAction(Request $request){
        $page = $request->query->get('page', 1);
        $topCiyoService = $this->get('lychee.module.activitytopciyobyweek.topciyobyweek');
        $count = 10;
        $topCiyoList = $topCiyoService->getList($page,$count);
//        return new JsonResponse($topCiyoList) ;
        $total = count($topCiyoList);
        $totalPage = ceil($total/$count);

//        $postIdList = [];
//        foreach($topCiyoList as $topCiyo)
//        {
//            foreach($topCiyo->videoList as $o)
//                $postIdList[] = $o->id;
//        }

//        $postService = $this->get('lychee.module.post');
//        $postList = $postService->fetch($postIdList);
//        $postDataList = [];
//        foreach($postList as $p){
//            $postDataList[$p->id] = $p;
//        }
        return $this->response($this->getTitle()." -- 列表", [
            'topCiyoList' => $topCiyoList,
//            'postDataList' => $postDataList,
            'page' => $page,
            'totalPage' => $totalPage,
            'now' => new \DateTime(),
        ]);
    }
    /**
     * @Route("/create")
     * @Template
     * @param Request $request
     * @return array
     */
    public function createAction(Request $request){
        return $this->response($this->getTitle()." -- 创建", [
            'now' => new \DateTime(),
        ]);
    }
    /**
     * @Route("/docreate")
     * @Method("POST")
     * @Template
     * @param Request $request
     * @return array
     */
    public function doCreateAction(Request $request){
//        $id = $request->request->get('id');
        $name = $request->request->get('name');
        $startTime = $request->request->get('start_time');
        $endTime = $request->request->get('end_time');
        $videoIdList = $request->request->get('videoId');
        $reasonTextList = $request->request->get('reasonText');
        $topCiyoService = $this->get('lychee.module.activitytopciyobyweek.topciyobyweek');
        $videoIdReasonList = array_combine($videoIdList,$reasonTextList);

        if($startTime) $startTime = strtotime($startTime);
        if($endTime) $endTime = strtotime($endTime);
//        $id = (int)$id;

        $returnResult = $errorResult = [];

        $postService = $this->get('lychee.module.post');
        $postList = $postService->fetch($videoIdList);

        foreach($postList as $post){
            if($post->type != \Lychee\Bundle\CoreBundle\Entity\Post::TYPE_SHORT_VIDEO){
                $returnResult[$post->id] = -101;
                $errorResult[$post->id] = ["id"=>$post->id,'err'=>-101];
            }else{
                $returnResult[$post->id] = 0;
            }
        }
        if(count($postList) != count($videoIdList)){
            foreach($videoIdList as $vid)
            {
                if(!isset($returnResult[$vid]))
                {
                    $returnResult[$vid] = -100;
                    $errorResult[$vid] = ["id"=>$vid,"err"=>-100];
                }
            }
        }
        if($errorResult){
            $code = -1;
            $data = $errorResult;
        }else{
            $id = 0;
            $topCiyoId = $topCiyoService->create($id , $name,$startTime,$endTime,$videoIdReasonList);
            $code = 0;
            $data = ['ciyoId'=>$topCiyoId];
        }
        $return = ['data'=>$data,'code'=>$code];
        return new JsonResponse($return);
    }
    /**
     * @Route("/delete")
     * @Template
     * @param Request $request
     * @return array
     */
    public function deleteAction(Request $request){
        $id = $request->request->get('id');
        $topCiyoService = $this->get('lychee.module.activitytopciyobyweek.topciyobyweek');
        $return = $topCiyoService->deleteByTopCiyoId($id);
        return new JsonResponse($return) ;
    }
}