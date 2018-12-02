<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Bundle\ApiBundle\Error\CommonError;
use Lychee\Bundle\ApiBundle\Error\Error;
use Lychee\Module\ContentManagement\Entity\ReviewState;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Lychee\Module\ContentManagement\Entity\AppLaunchImage;
use Lychee\Module\ContentManagement\Domain\WhiteList;
use Lychee\Module\ContentManagement\ReviewStateService;

class AppContentController extends Controller {

    /**
     * @Route("/app/launch_image")
     * @Method("GET")
     * @ApiDoc(
     *   section="misc",
     *   description="根据长宽比返回对应的图片链接",
     *   parameters={
     *     {"name"="width", "dataType"="integer", "required"=true, "description"="屏幕宽度，单位为像素"},
     *     {"name"="height", "dataType"="integer", "required"=true, "description"="屏幕高度，单位为像素"}
     *   }
     * )
     */
    public function launchImage(Request $request) {
        $width = $this->requireInt($request->query, 'width');
        $height = $this->requireInt($request->query, 'height');

        $images = $this->contentManagement()->fetchAppLaunchImages();
        if (empty($images)) {
            return $this->dataResponse(array());
        } else {
            $aspectRatio = $width / $height;
            usort($images, function($a, $b) use ($aspectRatio) {
                /** @var AppLaunchImage $a */
                /** @var AppLaunchImage $b */
                $aAspectRatio = $a->getWidth() / $a->getHeight();
                $bAspectRatio = $b->getWidth() / $b->getHeight();
                $aDelta = abs($aAspectRatio - $aspectRatio);
                $bDelta = abs($bAspectRatio - $aspectRatio);
                if ($aDelta == $bDelta) {
                    return 0;
                } else {
                    return ($aDelta < $bDelta) ? -1 : 1;
                }
            });

            $url = $images[0]->getUrl();
            return $this->dataResponse(array('url' => $url));
        }
    }

    /**
     * @Route("/app/domains")
     * @Method("GET")
     * @ApiDoc(
     *   section="misc",
     *   description="返回发帖中，当前可用的域名列表"
     * )
     */
    public function getAvailableDomains() {
        /** @var WhiteList $domainWhiteList */
        $domainWhiteList = $this->get('lychee.module.content_management.domain_whitelist');
        $items = $domainWhiteList->getAllItems();
        $result = array();
        foreach ($items as $item) {
            $result[] = array(
                'name' => $item->name,
                'prefix' => $item->domain,
                'type' => $item->type
            );
        }

        return $this->dataResponse($result);
    }

    /**
     * @Route("/app/review_state")
     * @Method("GET")
     * @ApiDoc(
     *   section="misc",
     *   description="返回指定渠道和指定版本的app审核状态",
     *   parameters={
     *     {"name"="channel", "dataType"="string", "required"=true, "description"="渠道"},
     *     {"name"="version", "dataType"="string", "required"=true, "description"="版本号"}
     *   }
     * )
     */
    public function getChannelReviewState(Request $request) {
        $channel = $this->requireParam($request->query, 'channel');
        $version = $this->requireParam($request->query, 'version');

        /** @var ReviewStateService $reivewStateService */
        $reivewStateService = $this->get('lychee.module.content_management.review_state');
        if ($reivewStateService->channelInReview(ReviewState::APP_CIYUANSHE, $channel, $version)) {
            return $this->sucessResponse();
        } else {
            return $this->failureResponse();
        }
    }

    /**
     * /**
     * @Route("/app/dynamic_menu_items")
     * @Method("GET")
     * @ApiDoc(
     *   section="misc",
     *   description="返回动态菜单项",
     *   parameters={
     *
     *   }
     * )
     */
    public function getDynamicMenuItems(Request $request) {
        return $this->dataResponse(array(array(
            'link' => 'https://shop155638961.taobao.com',
            'title' => '万事屋',
            'subtitle' => '限量热卖中'
        )));
    }

    /**
     * @Route("/misc/video_cover")
     * @Method("GET")
     * @ApiDoc(
     *   section="misc",
     *   description="返回指定视频网页的视频封面,",
     *   parameters={
     *     {"name"="video_url", "dataType"="string", "required"=true, "description"="视频网页的url"},
     *   }
     * )
     */
    public function getVideoCover(Request $request) {
        $websiteUrl = $this->requireParam($request->query, 'video_url');
        $c = parse_url($websiteUrl, PHP_URL_HOST);
        if ($c === false || (is_array($c) && !isset($c['host']))) {
            return $this->errorsResponse(CommonError::ParameterInvalid('video_url', $websiteUrl));
        }
        $host = is_array($c) ? $c['host'] : $c;
        $validHosts = array(
            'video.weibo.com',
            'bilibili.com',
            'www.miaopai.com',
            'm.miaopai.com'
        );

        $valid = false;
        $hostLen = strlen($host);
        foreach ($validHosts as $validHost) {
            $validLen = strlen($validHost);
            if ($hostLen < $validLen) {
                continue;
            }
            if (substr_compare($host, $validHost, $hostLen - $validLen, $validLen, true) == 0) {
                $valid = true;
                break;
            }
        }

        if (!$valid) {
            $error = new Error(CommonError::CODE_ParameterInvalid, 'invalid host.', '暂时不支持这类视频网站');
            return $this->errorsResponse(array($error));
        }

        $r = $this->contentManagement()->fetchVideoCover($websiteUrl, true);
        if ($r == false) {
            return $this->dataResponse(array(
                'cover_url' => 'http://qn.ciyocon.com/app/video_cover_default.png',
                'width' => 600, 'height' => 450
                ));
        } else {
            return $this->dataResponse(array(
                'cover_url' => $r['coverUrl'], 'width' => $r['width'], 'height' => $r['height']
            ));
        }
    }

} 
