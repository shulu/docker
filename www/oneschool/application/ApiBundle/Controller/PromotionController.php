<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Bundle\ApiBundle\DataSynthesizer\BannerSynthesizer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;

class PromotionController extends Controller {
    /**
     * @Route("/promotion")
     * @Method("GET")
     * @ApiDoc(
     *   section="misc",
     *   parameters={
     *     {"name"="device_resolution", "dataType"="string", "required"=false,
     *       "description"="屏幕分辨率, 形如{宽}x{高}的字符串。如，320x480。以像素为单位"}
     *   }
     * )
     */
    public function getAction() {
        $banners = $this->recommendationBanner()->fetchAvailableBanners();
        $synthesizer = new BannerSynthesizer($banners);
        $result = $synthesizer->synthesizeAll();
        return $this->dataResponse($result);
    }

    /**
     * @Route("/promotion/list")
     * @Method("GET")
     * @ApiDoc(
     *   section="promotion",
     *   description="获取指定次元下的推广内容",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="topic_id", "dataType"="integer", "required"=true,
     *       "description"="次元id"}
     *   }
     * )
     */
    public function getPromotionsAction(Request $request) {
        $account = $this->getAuthUser($request);
        $topicId = $this->requireId($request->query, 'topic_id');

        $promotionService = $this->get('lychee.module.promotion');
        $campaigns = $promotionService->fetchCampaignsByTopicId($topicId);

        return $this->dataResponse($campaigns);
    }
} 