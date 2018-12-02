<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Module\Measurement\ClientEvent\ClientEventRecorder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MeasurementController extends Controller {

    /**
     * @Route("/measurement/event/post_share/{postId}", defaults={"type": "post_share"})
     * @Route("/measurement/event/post_view/{postId}", defaults={"type": "post_view"})
     * @Route("/measurement/event/rec_banner/{bannerId}", defaults={"type": "rec_banner"})
     * @Route("/measurement/event/game_banner/{bannerId}", defaults={"type": "game_banner"})
     * @Route("/measurement/event/official_notification/{notificationId}", defaults={"type": "official_notification"})
     * @Route("/measurement/event/promotion_view/{promotionId}/{topicId}", defaults={"type": "promotion_view"})
     * @Method("POST")
     * @ApiDoc(
     *   section="measurement",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false}
     *   }
     * )
     */
    public function notifyEventAction(Request $request, $type) {
        $account = $this->getAuthUser($request);
        $userId = $account ? $account->id : null;

        $recorder = $this->get('lychee.module.measurement.client_event_recorder');
        if ($type == 'post_share') {
            $postId = $this->requireId($request, 'postId');
            $recorder->recordPostShare($postId, $userId);
        } else if ($type == 'post_view') {
            $postId = $this->requireId($request, 'postId');
            $platformStr = $request->get(self::CLIENT_PLATFORM_KEY);
            if ($platformStr == 'iphone' || $platformStr == 'ios') {
                $platform = ClientEventRecorder::PLATFORM_IOS;
            } else if ($platformStr == 'android') {
                $platform = ClientEventRecorder::PLATFORM_ANDROID;
            } else {
                $platform = null;
            }
            $recorder->recordPostView($postId, $userId, $platform);
        } else if ($type == 'rec_banner') {
            $bannerId = $this->requireId($request, 'bannerId');
            $recorder->recordRecBannerView($bannerId, $userId);
        } else if ($type == 'game_banner') {
            $bannerId = $this->requireId($request, 'bannerId');
            $recorder->recordGameBannerView($bannerId, $userId);
        } else if ($type == 'official_notification') {
            $notificationId = $this->requireId($request, 'notificationId');
            $recorder->recordOfficialNotificationView($notificationId, $userId);
        } else if ($type == 'promotion_view') {
            $promotionId = $this->requireId($request, 'promotionId');
            $topicId = $this->requireId($request, 'topicId');
            $recorder->recordPromotionView($promotionId, $userId, $topicId);
        }

        return $this->sucessResponse();
    }

}