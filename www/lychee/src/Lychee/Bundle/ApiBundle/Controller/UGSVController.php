<?php
/**
 * Created by tom on 2018/4/11 10:43
 */

namespace Lychee\Bundle\ApiBundle\Controller;
use Lychee\Component\Foundation\StringUtility;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
/**
 * @Route("/ugsv")
 */
class UGSVController extends Controller {

    /**
     * @Route("/bgm/hots")
     * @Method("GET")
     * @ApiDoc(
     *   section="UGSV",
     *   description="获取短视频的背景音乐列表",
     *   parameters={
     *     {"name"="cursor", "dataType"="integer", "required"=false,
     *       "description"="请求下一页时，需要将当前返回的next_cursor字段传递回来"},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认10，最多不超过100"}
     *   }
     * )
     */
    public function hotBGMListAction(Request $request) {
        list($cursor, $count) = $this->getCursorAndCount($request->query, 10, 100);
        $list = array();
        if ($cursor<0) {
            return $this->arrayResponse('list', array(), $cursor);
        }

        $response = new JsonResponse();
        $response->setPublic();
        $response->setTtl(60);

        list($list, $nextCursor) = $this->ugsvBGM()->getHotList($cursor, $count);
        $return = array();
        foreach ($list as $bgm) {

            $item = array();
            $item['id'] = $bgm->id;
            $item['url'] = $bgm->src;
            $item['cover'] = $bgm->cover;
            $item['name'] = $bgm->name;
            $item['singer_name'] = $bgm->singerName;
            $item['duration'] = vsprintf("%02d:%02d", array($bgm->duration/60, $bgm->duration%60));
            $item['duration_sec'] = intval($bgm->duration);

            $return[] = $item;
        }

        $response->setData(array(
            'list' => $return,
            'next_cursor' => strval($nextCursor)
        ));

        return $response;
    }

    /**
     * ###返回内容
     *
     * ```json
     * {
     * "result": 1,  //白名单验证结果，1：合法，0：不合法
     * "apply_url": "http://ciyocon.com/zm"  // 申请认证页地址
     * }
     * ```
     *
     * @Route("/isopen")
     * @Method("GET")
     * @ApiDoc(
     *   section="UGSV",
     *   description="判断某用户是否可以使用短视频功能",
     *   parameters={
     *     {"name"="uid", "dataType"="integer", "required"=true,
     *       "description"="用户id"}
     *   }
     * )
     */
    public function isOpenAction(Request $request) {
        $userId = $this->requireId($request->query, 'uid');
        $response = new JsonResponse();
        $response->setPublic();
        $response->setTtl(300);

        $r = $this->ugsvWhiteList()->fetchOne($userId);
        $isopen = 0;
        if ($r) {
            $isopen = 1;
        }

        $response->setData(array(
            'result' => $isopen,
            'apply_url' => 'http://www.ciyo.cn/zm',
        ));
        return $response;
    }



    /**
     * @Route("/video")
     * @Method("GET")
     * @ApiDoc(
     *   section="UGSV",
     *   description="获取单个短视频",
     *   parameters={
     *     {"name"="id", "dataType"="integer", "required"=true,
     *       "description"="帖子id"}
     *   }
     * )
     */
    public function getVideoAction(Request $request) {
        $postId = $this->requireId($request->query, 'id');
        $account = $this->getAuthUser($request);
        $synthesizer = $this->getSynthesizerBuilder()->buildListShortVideoPostSynthesizer([$postId], $account ? $account->id : 0);
        $post = $synthesizer->synthesizeOne($postId);
        if (empty($post)) {
            return $this->errorsResponse(\Lychee\Bundle\ApiBundle\Error\PostError::PostNotExist($postId));
        }

        $response = new JsonResponse();
        $response->setPublic();
        $response->setTtl(60);
        $response->setData($post);
        return $response;
    }


    /**
     * @Route("/video/signature")
     * @Method("GET")
     * @ApiDoc(
     *   section="UGSV",
     *   description="获取上传签名",
     *   parameters={
     *     {"name"="app_os_version", "dataType"="string", "required"=false, "description"="系统版本"},
     *     {"name"="app_ver", "dataType"="string", "required"=false, "description"="app版本"},
     *     {"name"="channel", "dataType"="string", "required"=false, "description"="渠道"},
     *     {"name"="client", "dataType"="string", "required"=false, "description"="客户端平台，ios|android"},
     *     {"name"="access_token", "dataType"="string", "required"=true}
     *   }
     * )
     */
    public function signatureAction(Request $request) {
        // 验证登陆态
        $account = $this->requireAuth($request);
        $isOpen = $this->ugsvWhiteList()->isExist($account->id);
        if (empty($isOpen)) {
            throw new \Lychee\Bundle\ApiBundle\Error\ErrorsException(
                array(\Lychee\Bundle\ApiBundle\Error\PostError::SVForbidden())
            );
        }
        // 确定 App 的云 API 密钥
        $secretId = $this->container->getParameter('tx_secret_id');
        $secretKey = $this->container->getParameter('tx_secret_key');

        // 确定签名的当前时间和失效时间
        $current = time();
        $expired = $current + 3600;  // 签名有效期：1小时

        // 向参数列表填入参数
        $args = array(
            "secretId" => $secretId,
            "currentTimeStamp" => $current,
            "expireTime" => $expired,
//            "procedure" => 'QCVB_ProcessUGCFile(0, 0, 0, 20)',
            "random" => rand());

        // 计算签名
        $orignal = http_build_query($args);
        $signature = base64_encode(hash_hmac('SHA1', $orignal, $secretKey, true).$orignal);
        return $this->dataResponse(array(
            'result' => $signature,
            'expires_at'=>$expired
        ));
    }



    /**
     * @Route("/video/recs")
     * @Method("GET")
     * @ApiDoc(
     *   section="UGSV",
     *   description="获取推荐的短视频列表",
     *   parameters={
     *     {"name"="cursor", "dataType"="integer", "required"=false,
     *       "description"="请求下一页时，需要将当前返回的next_cursor字段传递回来"},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function listVideosByRecAction(Request $request) {

        list($cursor, $count) = $this->getCursorAndCount($request->query, 1, 100);
        $list = array();
        if ($cursor<0) {
            return $this->arrayResponse('list', array(), $cursor);
        }
        $account = $this->getAuthUser($request);
        $postIds = $this->post()->fetchShortVideoIdsRand($cursor, $count, $nextCursor);
        $synthesizer = $this->getSynthesizerBuilder()->buildListShortVideoPostSynthesizer($postIds, $account ? $account->id : 0);
        $list = $synthesizer->synthesizeAll();

        $response = new JsonResponse();
        $response->setPublic();
        $response->setTtl(60);

        $response->setData(array(
            'posts' => $list,
            'next_cursor' => strval($nextCursor)
        ));

        return $response;
    }

    /**
     *
     * ### 返回内容 ###
     * ```json
     * {
     * "posts": [
     * {
     * "id": "130265670877185",
     * "topic": {
     * "id": "25362",
     * "create_time": 1411518077,
     * "title": "手绘",
     * "description": "触手聚集地～麻麻问我为什么跪着看屏幕(＞_＜）",
     * "index_image": "http://img.ciyocon.com/upload/4SbuqKByProP_VEaN2hTKrWgx1IwoUuugXpbmLReW9w",
     * "post_count": 87732,
     * "followers_count": 458856,
     * "private": false,
     * "apply_to_follow": false,
     * "color": "13df83",
     * "certified": false,
     * "following": false,
     * "manager": {
     * "id": "34839"
     * }
     * },
     * "create_time": 1528288624,
     * "type": "short_video",
     * "content": "这这这。。。。这个色调是什么？!!？？!",
     * "image_url": "http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/95e43e377447398156447254387/7447398156447254389.jpg",
     * "video_url": "http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/95e43e377447398156447254387/kpS1mUHD3lUA.mp4",
     * "annotation": {
     * "video_cover_width": 544,
     * "video_cover_height": 960,
     * "video_cover": "http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/95e43e377447398156447254387/7447398156447254389.jpg"
     * },
     * "author": {
     * "id": "1645074",
     * "nickname": "大石学长",
     * "avatar_url": "http://qn.ciyocon.com/upload/Fgf-OXnYaAcfAQXwaHIpHs3JW1Hw",
     * "gender": "male",
     * "level": 34,
     * "signature": "声出高楼人独坐，明月知我，赠与狂风和（四声）。",
     * "ciyoCoin": "0.00",
     * "certificate": "次元社最帅的学长"
     * },
     * "latest_likers": [],
     * "liked_count": 2,
     * "commented_count": 0,
     * "reposted_count": 0,
     * "liked": false,
     * "favorited": false
     * }
     * ],
     * "next_cursor": "130258562170881"
     * }
     * ```
     *
     * @Route("/video/timeline/user")
     * @Method("GET")
     * @ApiDoc(
     *   section="UGSV",
     *   description="获取某个用户发布的短视频列表",
     *   parameters={
     *     {"name"="uid", "dataType"="integer", "required"=true,
     *       "description"="作者用户id"},
     *     {"name"="cursor", "dataType"="integer", "required"=false,
     *       "description"="请求下一页时，需要将当前返回的next_cursor字段传递回来"},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function listVideosByUserAction(Request $request) {

        $userId = $this->requireId($request->query, 'uid');
        $account = $this->getAuthUser($request);

        list($cursor, $count) = $this->getCursorAndCount($request->query, 1, 100);
        if ($cursor<0) {
            return $this->arrayResponse('list', array(), $cursor);
        }
        $client = $request->query->get(self::CLIENT_PLATFORM_KEY, 'android');
        if ($account && $account->id==$userId) {
            $postIds = $this->post()->fetchShortVideoIdsByAuthorId($userId, $cursor, $count, $nextCursor);
        } else {
            $postIds = $this->post()->fetchShortVideoIdsByAuthorIdForClient($userId, $cursor, $count, $nextCursor, $client);
        }
        $synthesizer = $this->getSynthesizerBuilder()->buildListShortVideoPostSynthesizer($postIds, $account ? $account->id : 0);
        $list = $synthesizer->synthesizeAll();

        $response = new JsonResponse();
        $response->setPublic();
        $response->setTtl(60);

        $response->setData(array(
            'posts' => $list,
            'next_cursor' => strval($nextCursor)
        ));

        return $response;
    }

    /**
     * @return \Lychee\Module\Recommendation\Post\GroupPostsService
     */
    private function getGroupPostsService() {
        return $this->get('lychee.module.recommendation.group_posts');
    }

    /**
     *
     * ### 返回内容 ###
     * ```json
     * {
     * "posts": [
     * {
     * "id": "130265670877185",
     * "topic": {
     * "id": "25362",
     * "create_time": 1411518077,
     * "title": "手绘",
     * "description": "触手聚集地～麻麻问我为什么跪着看屏幕(＞_＜）",
     * "index_image": "http://img.ciyocon.com/upload/4SbuqKByProP_VEaN2hTKrWgx1IwoUuugXpbmLReW9w",
     * "post_count": 87732,
     * "followers_count": 458856,
     * "private": false,
     * "apply_to_follow": false,
     * "color": "13df83",
     * "certified": false,
     * "following": false,
     * "manager": {
     * "id": "34839"
     * }
     * },
     * "create_time": 1528288624,
     * "type": "short_video",
     * "content": "这这这。。。。这个色调是什么？!!？？!",
     * "image_url": "http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/95e43e377447398156447254387/7447398156447254389.jpg",
     * "video_url": "http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/95e43e377447398156447254387/kpS1mUHD3lUA.mp4",
     * "annotation": {
     * "video_cover_width": 544,
     * "video_cover_height": 960,
     * "video_cover": "http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/95e43e377447398156447254387/7447398156447254389.jpg"
     * },
     * "author": {
     * "id": "1645074",
     * "nickname": "大石学长",
     * "avatar_url": "http://qn.ciyocon.com/upload/Fgf-OXnYaAcfAQXwaHIpHs3JW1Hw",
     * "gender": "male",
     * "level": 34,
     * "signature": "声出高楼人独坐，明月知我，赠与狂风和（四声）。",
     * "ciyoCoin": "0.00",
     * "certificate": "次元社最帅的学长"
     * },
     * "latest_likers": [],
     * "liked_count": 2,
     * "commented_count": 0,
     * "reposted_count": 0,
     * "liked": false,
     * "favorited": false
     * }
     * ],
     * "next_cursor": "130258562170881"
     * }
     * ```
     *
     * @Route("/video/hots")
     * @Method("GET")
     * @ApiDoc(
     *   section="UGSV",
     *   description="获取热门的短视频列表",
     *   parameters={
     *     {"name"="cursor", "dataType"="integer", "required"=false,
     *       "description"="请求下一页时，需要将当前返回的next_cursor字段传递回来，为0即没有下一页"},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function listVideosByHotsAction(Request $request) {

        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 100);
        if ($cursor<0) {
            return $this->arrayResponse('list', array(), $cursor);
        }
        $account = $this->getAuthUser($request);
        $postIds = $this->post()->getHotShortVideoIds($cursor, $count, $nextCursor);
        // 降级策略，没有可用数据时，随机获取较新的记录顶替
        if (empty($postIds)) {
            $postIds = $this->post()->getTopNewLyShortVideoIdsRand($count);
        }
        $synthesizer = $this->getSynthesizerBuilder()->buildListShortVideoPostSynthesizer($postIds, $account ? $account->id : 0);
        $list = $synthesizer->synthesizeAll();

        $response = new JsonResponse();
        $response->setPublic();
        $response->setTtl(60);

        $response->setData(array(
            'posts' => $list,
            'next_cursor' => strval($nextCursor)
        ));

        return $response;
    }

    /**
     *
     * ### 返回内容 ###
     * ```json
     * {
     * "posts": [
     * {
     * "id": "130265670877185",
     * "topic": {
     * "id": "25362",
     * "create_time": 1411518077,
     * "title": "手绘",
     * "description": "触手聚集地～麻麻问我为什么跪着看屏幕(＞_＜）",
     * "index_image": "http://img.ciyocon.com/upload/4SbuqKByProP_VEaN2hTKrWgx1IwoUuugXpbmLReW9w",
     * "post_count": 87732,
     * "followers_count": 458856,
     * "private": false,
     * "apply_to_follow": false,
     * "color": "13df83",
     * "certified": false,
     * "following": false,
     * "manager": {
     * "id": "34839"
     * }
     * },
     * "create_time": 1528288624,
     * "type": "short_video",
     * "content": "这这这。。。。这个色调是什么？!!？？!",
     * "image_url": "http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/95e43e377447398156447254387/7447398156447254389.jpg",
     * "video_url": "http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/95e43e377447398156447254387/kpS1mUHD3lUA.mp4",
     * "annotation": {
     * "video_cover_width": 544,
     * "video_cover_height": 960,
     * "video_cover": "http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/95e43e377447398156447254387/7447398156447254389.jpg"
     * },
     * "author": {
     * "id": "1645074",
     * "nickname": "大石学长",
     * "avatar_url": "http://qn.ciyocon.com/upload/Fgf-OXnYaAcfAQXwaHIpHs3JW1Hw",
     * "gender": "male",
     * "level": 34,
     * "signature": "声出高楼人独坐，明月知我，赠与狂风和（四声）。",
     * "ciyoCoin": "0.00",
     * "certificate": "次元社最帅的学长"
     * },
     * "latest_likers": [],
     * "liked_count": 2,
     * "commented_count": 0,
     * "reposted_count": 0,
     * "liked": false,
     * "favorited": false
     * }
     * ],
     * "next_cursor": "130258562170881"
     * }
     * ```
     *
     * @Route("/video/newly")
     * @Method("GET")
     * @ApiDoc(
     *   section="UGSV",
     *   description="获取最新的短视频列表",
     *   parameters={
     *     {"name"="cursor", "dataType"="integer", "required"=false,
     *       "description"="请求下一页时，需要将当前返回的next_cursor字段传递回来，为0即没有下一页"},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function listVideosByNewlyAction(Request $request) {

        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 100);
        $list = array();
        if ($cursor<0) {
            return $this->arrayResponse('list', array(), $cursor);
        }
        $account = $this->getAuthUser($request);
        $postIds = $this->post()->getNewLyShortVideoIds($cursor, $count, $nextCursor);
        // 降级策略，没有可用数据时，随机获取较新的记录顶替
        if (empty($postIds)) {
            $postIds = $this->post()->getTopNewLyShortVideoIdsRand($count);
        }
        $synthesizer = $this->getSynthesizerBuilder()->buildListShortVideoPostSynthesizer($postIds, $account ? $account->id : 0);
        $list = $synthesizer->synthesizeAll();

        $response = new JsonResponse();
        $response->setPublic();
        $response->setTtl(60);

        $response->setData(array(
            'posts' => $list,
            'next_cursor' => strval($nextCursor)
        ));

        return $response;
    }



    /**
     *
     * ### 返回内容 ###
     * ```json
     * {
     * "rec_video_part1_count": 3, //推荐短视频列表 -> 第1部分单次请求数量
     * "rec_video_part2_count": 1  //推荐短视频列表 -> 第2部分单次请求数量
     * }
     * ```
     *
     * @Route("/config")
     * @Method("GET")
     * @ApiDoc(
     *   section="UGSV",
     *   description="获取短视频相关配置",
     *   parameters={
     *   }
     * )
     */
    public function getConfig() {
        $config = [];
        $config['rec_video_part1_count'] = 3;
        $config['rec_video_part2_count'] = 1;
        return $this->dataResponse($config);
    }



    /**
     *
     * ### 返回内容 ###
     * ```json
     * {
     * "posts": [
     * {
     * "id": "130265670877185",
     * "topic": {
     * "id": "25362",
     * "create_time": 1411518077,
     * "title": "手绘",
     * "description": "触手聚集地～麻麻问我为什么跪着看屏幕(＞_＜）",
     * "index_image": "http://img.ciyocon.com/upload/4SbuqKByProP_VEaN2hTKrWgx1IwoUuugXpbmLReW9w",
     * "post_count": 87732,
     * "followers_count": 458856,
     * "private": false,
     * "apply_to_follow": false,
     * "color": "13df83",
     * "certified": false,
     * "following": false,
     * "manager": {
     * "id": "34839"
     * }
     * },
     * "create_time": 1528288624,
     * "type": "short_video",
     * "content": "这这这。。。。这个色调是什么？!!？？!",
     * "image_url": "http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/95e43e377447398156447254387/7447398156447254389.jpg",
     * "video_url": "http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/95e43e377447398156447254387/kpS1mUHD3lUA.mp4",
     * "annotation": {
     * "video_cover_width": 544,
     * "video_cover_height": 960,
     * "video_cover": "http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/95e43e377447398156447254387/7447398156447254389.jpg"
     * },
     * "author": {
     * "id": "1645074",
     * "nickname": "大石学长",
     * "avatar_url": "http://qn.ciyocon.com/upload/Fgf-OXnYaAcfAQXwaHIpHs3JW1Hw",
     * "gender": "male",
     * "level": 34,
     * "signature": "声出高楼人独坐，明月知我，赠与狂风和（四声）。",
     * "ciyoCoin": "0.00",
     * "certificate": "次元社最帅的学长"
     * },
     * "latest_likers": [],
     * "liked_count": 2,
     * "commented_count": 0,
     * "reposted_count": 0,
     * "liked": false,
     * "favorited": false
     * }
     * ],
     * "next_cursor": "130258562170881"
     * }
     * ```
     *
     * @Route("/video/recs/part1")
     * @Method("GET")
     * @ApiDoc(
     *   section="UGSV",
     *   description="获取推荐短视频列表的第1部分",
     *   parameters={
     *     {"name"="cursor", "dataType"="integer", "required"=false,
     *       "description"="请求下一页时，需要将当前返回的next_cursor字段传递回来，为0即没有下一页"},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function listVideosByRecPart1Action(Request $request) {

        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 100);
        if ($cursor<0) {
            return $this->arrayResponse('list', array(), $cursor);
        }
        $account = $this->getAuthUser($request);
        $postIds = $this->post()->getRecShortVideoIds($cursor, $count, $nextCursor);
        shuffle($postIds);
        // 降级策略，没有可用数据时，随机获取较热门的记录顶替
        if (empty($postIds)) {
            $postIds = $this->post()->getTopHotShortVideoIdsRand($count);
        }
        $synthesizer = $this->getSynthesizerBuilder()->buildListLiveShortVideoPostSynthesizer($postIds, $account ? $account->id : 0);
        $list = $synthesizer->synthesizeAll();

        $response = new JsonResponse();
        $response->setPublic();
        $response->setTtl(60);

        $response->setData(array(
            'posts' => $list,
            'next_cursor' => strval($nextCursor)
        ));

        return $response;
    }

    /**
     *
     * ### 返回内容 ###
     * ```json
     * {
     * "posts": [
     * {
     * "id": "130265670877185",
     * "topic": {
     * "id": "25362",
     * "create_time": 1411518077,
     * "title": "手绘",
     * "description": "触手聚集地～麻麻问我为什么跪着看屏幕(＞_＜）",
     * "index_image": "http://img.ciyocon.com/upload/4SbuqKByProP_VEaN2hTKrWgx1IwoUuugXpbmLReW9w",
     * "post_count": 87732,
     * "followers_count": 458856,
     * "private": false,
     * "apply_to_follow": false,
     * "color": "13df83",
     * "certified": false,
     * "following": false,
     * "manager": {
     * "id": "34839"
     * }
     * },
     * "create_time": 1528288624,
     * "type": "short_video",
     * "content": "这这这。。。。这个色调是什么？!!？？!",
     * "image_url": "http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/95e43e377447398156447254387/7447398156447254389.jpg",
     * "video_url": "http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/95e43e377447398156447254387/kpS1mUHD3lUA.mp4",
     * "annotation": {
     * "video_cover_width": 544,
     * "video_cover_height": 960,
     * "video_cover": "http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/95e43e377447398156447254387/7447398156447254389.jpg"
     * },
     * "author": {
     * "id": "1645074",
     * "nickname": "大石学长",
     * "avatar_url": "http://qn.ciyocon.com/upload/Fgf-OXnYaAcfAQXwaHIpHs3JW1Hw",
     * "gender": "male",
     * "level": 34,
     * "signature": "声出高楼人独坐，明月知我，赠与狂风和（四声）。",
     * "ciyoCoin": "0.00",
     * "certificate": "次元社最帅的学长"
     * },
     * "latest_likers": [],
     * "liked_count": 2,
     * "commented_count": 0,
     * "reposted_count": 0,
     * "liked": false,
     * "favorited": false
     * }
     * ],
     * "next_cursor": "130258562170881"
     * }
     * ```
     *
     * @Route("/video/recs/part2")
     * @Method("GET")
     * @ApiDoc(
     *   section="UGSV",
     *   description="获取推荐短视频列表的第2部分",
     *   parameters={
     *     {"name"="cursor", "dataType"="integer", "required"=false,
     *       "description"="请求下一页时，需要将当前返回的next_cursor字段传递回来，为0即没有下一页"},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function listVideosByRecPart2Action(Request $request) {

        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 100);
        $list = array();
        if ($cursor<0) {
            return $this->arrayResponse('list', array(), $cursor);
        }
        $account = $this->getAuthUser($request);
        $postIds = $this->post()->getNewLyShortVideoIds($cursor, $count, $nextCursor);
        // 降级策略，没有可用数据时，随机获取较新的记录顶替
        if (empty($postIds)) {
            $postIds = $this->post()->getTopNewLyShortVideoIdsRand($count);
        }
        $synthesizer = $this->getSynthesizerBuilder()->buildListLiveShortVideoPostSynthesizer($postIds, $account ? $account->id : 0);
        $list = $synthesizer->synthesizeAll();

        $response = new JsonResponse();
        $response->setPublic();
        $response->setTtl(60);

        $response->setData(array(
            'posts' => $list,
            'next_cursor' => strval($nextCursor)
        ));

        return $response;
    }

    /**
     *
     * ###返回内容
     *
     * ```json
     * {
     * "result": true,  //true:成功，false:失败
     * }
     * ```
     *
     * @Route("/video/stop_play")
     * @Method("POST")
     * @ApiDoc(
     *   section="UGSV",
     *   description="视频播放结束事件",
     *   parameters={
     *     {"name"="start_time", "dataType"="integer", "required"=true,
     *       "description"="开始播放时间戳"},
     *     {"name"="end_time", "dataType"="integer", "required"=true,
     *       "description"="停止播放时间戳"},
     *     {"name"="pause_duration", "dataType"="integer", "required"=true,
     *       "description"="暂停时长"},
     *     {"name"="pid", "dataType"="integer", "required"=true,
     *       "description"="帖子id"},
     *     {"name"="uuid", "dataType"="string", "required"=true,
     *       "description"="设备id"},
     *     {"name"="access_token", "dataType"="string", "required"=false,
     *       "description"="用户登录token"}
     *   }
     * )
     *
     */
    public function stopPlayVideoAction(Request $request)
    {
        $account = $this->getAuthUser($request);
        $deviceId = $this->requireParam($request->request, self::CLIENT_DEVICE_ID_KEY);
        $startTime = $this->requireInt($request->request, 'start_time');
        $endTime = $this->requireInt($request->request, 'end_time');
        $pid = $this->requireInt($request->request, 'pid');
        $pauseDuration = $request->request->getInt('pause_duration');

        $event = [];
        $event['userId'] = $account?$account->id:0;
        $event['postId'] = $pid;
        $event['startTime'] = $startTime;
        $event['endTime'] = $endTime;
        $event['pauseDuration'] = $pauseDuration;
        $event['deviceId'] = $deviceId;
        $event['time'] = time();
        $this->get('lychee.dynamic_dispatcher_async')->dispatch('short_video.stop_play', $event);

        return $this->dataResponse(['result'=>true]);
    }

}
