<?php
/**
 * Created by PhpStorm.
 * User: ys160726
 * Date: 2017/1/11
 * Time: 下午4:45
 */

namespace Lychee\Bundle\ApiBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;

class ShareController extends Controller
{
    /**
     * @Route("/share/redirect", name="ios_share_live_redirect")
     * @Method("GET")
     * @ApiDoc(
     *     section="share",
     *   parameters={
     *     {"name"="action", "dataType"="string", "required"=true, "description"="固定值"},
     *     {"name"="live_id", "dataType"="string", "required"=true, "description"="直播ID"}
     *   }
     * )
     * @param Request $request
     *
     */
    public function shareRedirectAction(Request $request) {
        $env = $this->get('kernel')->getEnvironment();
        if ('dev' === $env) {
            return $this->redirect('http://192.168.9.90:8900/download/redirect');
        }
        else {
            return $this->redirect('http://www.ciyo.cn/download/redirect');
        }
    }

}