<?php
namespace Lychee\Bundle\WebsiteBundle\Controller;

use Lychee\Bundle\CoreBundle\Controller\Controller;
use Lychee\Module\ContentManagement\Entity\ReviewState;
use Lychee\Module\ContentManagement\ReviewStateService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CityLineController extends Controller {

    /**
     * @Route("/cityline/download/redirect")
     * @Route("/cityline/download/app/{platform}", name="cityline_download_app")
     * @Route("/unknownmessage/download/redirect")
     * @Route("/unknownmessage/download/app/{platform}", name="unknownmessage_download_app")
     */
    public function redirectDownloadAction(Request $request, $platform = null) {
        $userAgent = $request->server->get('HTTP_USER_AGENT');

        $iphoneLink = 'https://itunes.apple.com/us/app/yi-shi-kong-tong-xun/id1111448349?l=zh&ls=1&mt=8';
        $wechatLink = 'http://a.app.qq.com/o/simple.jsp?pkgname=com.yinshi.cityline';
        $androidLink = 'http://qn.ciyocon.com/unknownmessage/unknownmessage.apk?v='.time();

        $downloadUrl = null;
        if ($platform !== null) {
            if ($platform === 'iphone') {
                $downloadUrl = $iphoneLink;
            } else if ($platform === 'wechat') {
                $downloadUrl = $wechatLink;
            } else if ($platform === 'android') {
                $downloadUrl = $androidLink;
            }
        } else {
            if (stripos($userAgent, 'MicroMessenger') !== false) {
                $downloadUrl = $wechatLink;
            } else if (stripos($userAgent, 'Android') !== false) {
                $downloadUrl = $androidLink;
            } else if (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPod') !== false) {
                $downloadUrl = $iphoneLink;
            } else {
                $downloadUrl = $androidLink;
            }
        }
        if ($downloadUrl == null) {
            throw $this->createNotFoundException();
        }

        return $this->redirect($downloadUrl);
    }

    /**
     * @Method("GET")
     * @Route("/cityline/in_review")
     * @Route("/unknownmessage/in_review")
     */
    public function inReviewAction(Request $request) {
        $channel = $request->query->get('channel');
        $clientVersion = $request->query->get('app_ver');

        if (!$channel || !$clientVersion) {
            return new JsonResponse(array('result' => false));
        }

        /** @var ReviewStateService $reivewStateService */
        $reivewStateService = $this->get('lychee.module.content_management.review_state');
        $inReview = $reivewStateService->channelInReview(ReviewState::APP_UNKNOWN_MESSAGE, $channel, $clientVersion);
        return new JsonResponse(array('result' => $inReview));
    }

}
