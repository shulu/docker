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
 * @Route("/wxmini")
 */
class WXMiniController extends Controller {

    /**
     *
     * ### 返回内容 ###
     * ```json
     * {
     * "result": "oegsY0dviwppTslkjxdReoylg6jE"
     * }
     * ```
     *
     * @Route("/openid")
     * @Method("GET")
     * @ApiDoc(
     *   section="weixin_mini",
     *   description="在微信小程序里获取用户的openid",
     *   parameters={
     *     {"name"="code", "dataType"="string", "required"=true,
     *       "description"="临时登录token"}
     *   }
     * )
     */
    public function getOpenIdAction(Request $request) {

        $appId = 'wx75cd3c83161eff72';
        $secret = '19fd3abb3c12a46d1c9f29efc47a8561';

        $jsCode = $this->requireParam($request, 'code');
        if (empty($jsCode)) {

            return $this->dataResponse([
                'result'=>''
            ]);
        }
        $query = [];
        $query['appid'] = $appId;
        $query['secret'] = $secret;
        $query['js_code'] = $jsCode;
        $query['grant_type'] = 'authorization_code';

        $client =  new \GuzzleHttp\Client([
            'base_uri' => 'https://api.weixin.qq.com/'
        ]);
        $result = $client->get('sns/jscode2session', ['query'=>$query])
            ->getBody()
            ->getContents();
        $result = json_decode($result, true);

        $openid = '';
        if (isset($result['openid'])) {
            $openid = $result['openid'];
        }

        return $this->dataResponse([
            'result'=>$openid
        ]);
    }




}
