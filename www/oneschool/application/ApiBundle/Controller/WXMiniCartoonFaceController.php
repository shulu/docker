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
 * @Route("/wxmini/cartoon_face")
 */
class WXMiniCartoonFaceController extends Controller {

    /**
     *
     * ### 返回内容 ###
     * ```json
     * {
     * "result": true
     * }
     * ```
     *
     * @Route("/is_open")
     * @Method("GET")
     * @ApiDoc(
     *   section="weixin_mini_cartoon_face",
     *   description="判断用户是否可以下载所有图片",
     *   parameters={
     *     {"name"="openid", "dataType"="string", "required"=true,
     *       "description"="用户openid"}
     *   }
     * )
     */
    public function isOpenDownLoadAllAction(Request $request) {

        $openid = $this->requireParam($request->query, 'openid');
        if (empty($openid)) {
            return $this->failureResponse();
        }
        return $this->sucessResponse();
    }


    /**
     *
     * ### 返回内容 ###
     * ```json
     * {
     * "result": true
     * }
     * ```
     *
     * @Route("/open")
     * @Method("POST")
     * @ApiDoc(
     *   section="weixin_mini_cartoon_face",
     *   description="解锁用户下载所有图片的权限",
     *   parameters={
     *     {"name"="code", "dataType"="string", "required"=true,
     *       "description"="临时登录token"},
     *     {"name"="shareDecryptData", "dataType"="string", "required"=true,
     *       "description"="关于群聊的分享信息，wx.getShareInfo -> encryptedData"},
     *     {"name"="shareIV", "dataType"="string", "required"=true,
     *       "description"="关于群聊的分享信息，wx.getShareInfo -> iv"},
     *   }
     * )
     */
    public function openDownLoadAllAction(Request $request) {
        $jsCode = $this->requireParam($request->request, 'code');
        $shareDecryptData = $this->requireParam($request->request, 'shareDecryptData');
        $shareIV = $this->requireParam($request->request, 'shareIV');

        $data = $this->decryptData($jsCode, $shareDecryptData, $shareIV);
        if (empty($data['openGId'])) {
            return $this->failureResponse();
        }

        $gid = $data['openGId'];

        return $this->sucessResponse();
    }

    /**
     * 检验数据的真实性，并且获取解密后的明文.
     * @param $encryptedData string 加密的用户数据
     * @param $iv string 与用户数据一同返回的初始向量
     * @param $data string 解密后的原文
     *
     * @return array 成功0，失败返回对应的错误码
     */
    public function decryptData( $code, $encryptedData, $iv )
    {
        $appId = 'wx75cd3c83161eff72';
        $secret = '19fd3abb3c12a46d1c9f29efc47a8561';

        $query = [];
        $query['appid'] = $appId;
        $query['secret'] = $secret;
        $query['js_code'] = $code;
        $query['grant_type'] = 'authorization_code';

        $client =  new \GuzzleHttp\Client([
            'base_uri' => 'https://api.weixin.qq.com/'
        ]);
        $result = $client->get('sns/jscode2session', ['query'=>$query])
            ->getBody()
            ->getContents();
        $result = json_decode($result, true);
        if (empty($result['session_key'])) {
            return [];
        }

        $sessionKey = $result['session_key'];

        if (strlen($sessionKey) != 24) {
            return [];
        }
        $aesKey=base64_decode($sessionKey);


        if (strlen($iv) != 24) {
            return [];
        }
        $aesIV=base64_decode($iv);

        $aesCipher=base64_decode($encryptedData);

        $result=openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

        $data=json_decode( $result, true);
        if(empty($data)) {
            return [];
        }
        if( $data['watermark']['appid'] != $appId ) {
            return [];
        }
        return $data;
    }


    /**
     *
     * ### 返回内容 ###
     * ```json
     * {
     *   "list" : [
     *    {"id":1, "path":"1.jpg","is_free":true},
     *    {"id":2, "path":"2.jpg","is_free":true}
     *   ],
     *   "cursor":0
     * }
     *
     * ```
     *
     * @Route("/list")
     * @Method("GET")
     * @ApiDoc(
     *   section="weixin_mini_cartoon_face",
     *   description="判断用户是否可以下载所有图片",
     *   parameters={
     *     {"name"="type", "dataType"="integer", "required"=false,
     *       "description"="1:女生图片，2：男生图片"},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过100"}
     *   }
     * )
     */
    public function getListAction(Request $request) {

        $type = $request->query->get('type', 1);
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 500);

        $list = [];

        for ($i=1; $i<=100; $i++) {
            $list[] = [
                'id'=>$i,
                'path'=>$type.'_'.$i.'.jpg',
                'is_free'=>true,
            ];
        }

        return $this->dataResponse([
            'list'=>$list,
            'cursor'=>0
        ]);
    }



}
