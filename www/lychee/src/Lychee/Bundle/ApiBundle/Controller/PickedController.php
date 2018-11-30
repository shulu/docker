<?php
/**
 * Created by tom on 2018/4/11 10:43
 */

namespace Lychee\Bundle\ApiBundle\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
/**
 * @Route("/picked")
 */
class PickedController extends Controller {


    /**
     *
     * ### 返回内容 ###
     * ```json
     * {
     * "list": [
     * {
     * "id": "130265670877185", //帖子id
     * "stage": 1, // 帖子所属期数
     * "reason": "理由",  //推荐理由
     * "image_url": "http://qn.ciyocon.com/ugsvcover/3c32e01aca46ab14f3060d68fc7d87ee", //视频封面
     * "video_url": "http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/95e43e377447398156447254387/kpS1mUHD3lUA.mp4", //视频地址
     * "author": {
     * "id": "1645074",  //作者用户id
     * "nickname": "大石学长", //作者昵称
     * "avatar_url": "http://qn.ciyocon.com/upload/Fgf-OXnYaAcfAQXwaHIpHs3JW1Hw" // 作者头像
     * }
     * }
     * ],
     * "next_cursor": "130258562170881"
     * }
     * ```
     *
     * @Route("/video/list")
     * @Method("GET")
     * @ApiDoc(
     *   section="picked",
     *   description="精选视频列表",
     *   parameters={
     *     {"name"="stage", "dataType"="integer", "required"=false,
     *       "description"="指定期数，为0即获取当前期"},
     *     {"name"="cursor", "dataType"="integer", "required"=false,
     *       "description"="请求下一页时，需要将当前返回的next_cursor字段传递回来，为0即没有下一页"},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function videoListAction(Request $request) {
        list($cursor, $count) = $this->getCursorAndCount($request->query, 10, 100);
        if ($cursor<0) {
            return $this->arrayResponse('list', array(), $cursor);
        }

        $topCiyoService = $this->get('lychee.module.activitytopciyobyweek.topciyobyweek');
        $id = (int)$request->query->get('id');
        $page = $cursor;
        $nextCursor = $previousCursor = 0;
        if($id>0){
            $topCiyo = $topCiyoService->getById($id);
        }else{
            $topCiyo = $topCiyoService->getCurrentTopCiyoByWeek();
        }
        if(empty($topCiyo)){
            return $this->arrayResponse('list', array(), $cursor);
        }

        $list = [];
        foreach($topCiyo->videoList as $v){
            $list[] = [
                'id' => $v->id,
                'reason' => $v->reasonText,
                'stage' => $v->id,
                'author' => [
                    'id' => $v->authorId,
                    'nickname' => $v->author->nickname,
                ],
                'image_url' => $v->imageUrl,
            ];
        }

        $data = [
            'list' => $list,
            'next' => strval($nextCursor),
            'pervious' => strval($previousCursor),
        ];

        return $this->dataResponse($data , true , 60);
    }

    /**
     *
     * ### 返回内容 ###
     * ```json
     * {
     * "result": 10
     * }
     * ```
     *
     * @Route("/video/final_stage")
     * @Method("GET")
     * @ApiDoc(
     *   section="picked",
     *   description="获取最后期数",
     *   parameters={
     *   }
     * )
     */
    public function videoFinalStageAction() {
        $response = new JsonResponse();
        $response->setPublic();
        $response->setTtl(60);

        $response->setData(array(
            'result' => 1,
        ));
        return $response;
    }




}
