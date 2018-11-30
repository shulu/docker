<?php
/**
 * Created by PhpStorm.
 * User: ys160726
 * Date: 2017/1/10
 * Time: 下午4:16
 */

namespace Lychee\Bundle\WebsiteBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route ("/live")
 */
class LiveController extends Controller
{
    /**
     * @param Request $request
     * @return array
     * @Route("/share")
     * @Template
     */
    public function shareAction(Request $request) {
        $data = $request->query->get('param');
        $data = urldecode($data);
        $data = json_decode($data);
        $nikeName = '';
        $cover = '';
        $onlineUsers = 0;
        $liveId = '';
        $liveName = '';
        $level = 0;
        $uid = 0;
        $host = 'https://api.ciyo.cn';
        $env = $this->get('kernel')->getEnvironment();
        $userAgent = $request->server->get('HTTP_USER_AGENT');
        if (isset($data -> nickname)) {
            $nikeName = $data -> nickname;
        }
        if (isset($data -> cover)) {
            $cover = $data -> cover;
        }
        if (isset($data -> online_users)) {
            $onlineUsers = $data -> online_users;
        }
        if (isset($data -> liveid)) {
            $liveId = $data -> liveid;
        }
        if (isset($data -> livename)) {
            $liveName = $data -> livename;
        }
        if (isset($data -> level)) {
            $level = $data -> level;
        }
        if (isset($data -> pid)) {
            $uid = $data -> pid;
        }
        if ('dev' === $env) {
            $host = 'http://192.168.9.90:8300';
        }

       return array(
           'nikeName' => $nikeName,
           'avatar' => $cover,
           'level' => $level,
           'liveName' => $liveName,
           'onlineUsers' => $onlineUsers,
           'host' => $host,
           'userAgent' => $userAgent,
           'uid' => $uid
       );
   }

}