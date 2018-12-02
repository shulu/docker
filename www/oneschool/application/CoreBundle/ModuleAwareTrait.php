<?php

namespace Lychee\Bundle\CoreBundle;

use Lychee\Module\Account\AccountService;
use Lychee\Module\Authentication\AuthenticationService;
use Lychee\Module\ExtraMessage\EMUserService;
use Lychee\Module\ExtraMessage\Entity\EMUser;
use Lychee\Module\ExtraMessage\EMPictureRecordService;
use Lychee\Module\ExtraMessage\ExtraMessageService;
use Lychee\Module\ExtraMessage\EMAuthenticationService;
use Lychee\Module\Favorite\FavoriteService;
use Lychee\Module\Live\LiveService;
use Lychee\Module\Notification\NotificationService;
use Lychee\Module\Notification\Push\PushSettingManager;
use Lychee\Module\Payment\CiyoCoinPurchaseRecorder;
use Lychee\Module\Payment\PaymentIAPRefundManager;
use Lychee\Module\Payment\ProductManager;
use Lychee\Module\Payment\PurchaseRecorder;
use Lychee\Module\Relation\RelationService;
use Lychee\Module\Post\PostService;
use Lychee\Module\Topic\TopicService;
use Lychee\Module\Topic\Following\TopicFollowingService;
use Lychee\Module\Comment\CommentService;
use Lychee\Module\Notification\OfficialNotificationService;
use Lychee\Module\Like\LikeService;
use Lychee\Module\Activity\ActivityService;
use Lychee\Module\Recommendation\RecommendationService;
use Lychee\Module\Upload\UploadService;
use Lychee\Module\Report\ReportService;
use Lychee\Module\ContentManagement\ContentManagementService;
use Lychee\Module\Recommendation\BannerService;
use Lychee\Module\ContentManagement\InputDomainRecorder;
use Lychee\Module\ContentManagement\Domain\WhiteList;
use Lychee\Module\Analysis\AnalysisService;
use Lychee\Module\Account\Mission\MissionManager;
use Lychee\Module\Caitu\CaituService;

/**
 * Class ModuleAwareTrait
 * @package Lychee\Bundle\CoreBundle
 */
trait ModuleAwareTrait {
    use ContainerAwareTrait;

    /**
     * @return AccountService
     */
    public function account() {
        return $this->container()->get('lychee.module.account');
    }

    /**
     * @return MissionManager
     */
    public function missionManager() {
        return $this->container()->get('lychee.module.account.mission_manager');
    }

    /**
     * @return AuthenticationService
     */
    public function authentication() {
        return $this->container()->get('lychee.module.authentication');
    }

    /**
     * @return RelationService
     */
    public function relation() {
        return $this->container()->get('lychee.module.relation');
    }

    /**
     * @return PostService
     */
    public function post() {
        return $this->container()->get('lychee.module.post');
    }

    /**
     * @return CommentService
     */
    public function comment() {
        return $this->container()->get('lychee.module.comment');
    }

    /**
     * @return TopicService
     */
    public function topic() {
        return $this->container()->get('lychee.module.topic');
    }

    /**
     * @return TopicFollowingService
     */
    public function topicFollowing() {
        return $this->container()->get('lychee.module.topic.following');
    }

    /**
     * @return LikeService
     */
    public function like() {
        return $this->container()->get('lychee.module.like');
    }

    /**
     * @return FavoriteService
     */
    public function favorite() {
        return $this->container()->get('lychee.module.favorite');
    }

    /**
     * @return OfficialNotificationService
     */
    public function officialNotification() {
        return $this->container()->get('lychee.module.notification.official');
    }

    /**
     * @return NotificationService
     */
    public function notification() {
        return $this->container()->get('lychee.module.notification');
    }

    /**
     * @return ActivityService
     */
    public function activity() {
        return $this->container()->get('lychee.module.activity');
    }

    /**
     * @return RecommendationService
     */
    public function recommendation() {
        return $this->container()->get('lychee.module.recommendation');
    }

    /**
     * @return UploadService
     */
    public function upload() {
        return $this->container()->get('lychee.module.upload');
    }

    /**
     * @return ReportService
     */
    public function report() {
        return $this->container()->get('lychee.module.report');
    }

    /**
     * @return ContentManagementService
     */
    public function contentManagement() {
        return $this->container()->get('lychee.module.content_management');
    }

    /**
     * @return BannerService
     */
    public function recommendationBanner() {
        return $this->container()->get('lychee.module.recommendation.banner');
    }

    /**
     * @return InputDomainRecorder
     */
    public function inputDomainRecorder() {
        return $this->container()->get('lychee.module.input_domain_recorder');
    }

    /**
     * @return WhiteList
     */
    public function domainWhiteList() {
        return $this->container()->get('lychee.module.content_management.domain_whitelist');
    }

    /**
     * @return AnalysisService
     */
    public function analysis() {
        return $this->container()->get('lychee.module.analysis');
    }

    /**
     * @return \Lychee\Module\ContentManagement\StickerManagement
     */
    public function sticker() {
        return $this->container()->get('lychee.module.content_management.sticker');
    }

    /**
     * @return \Lychee\Component\Storage\StorageInterface
     */
    public function storage() {
        return $this->container()->get('lychee.component.storage');
    }

    /**
     * @return \Lychee\Module\Recommendation\SpecialSubjectManagement
     */
    public function specialSubject() {
        return $this->container()->get('lychee.module.special_subject');
    }

    /**
     * @return \Lychee\Bundle\AdminBundle\Service\FavorService
     */
    public function adminFavor() {
        return $this->container()->get('lychee_admin.service.favor');
    }

    /**
     * @return \Lychee\Module\Game\GameManager
     */
    public function game() {
        return $this->container()->get('lychee.module.game');
    }

    /**
     * @return \Lychee\Module\ContentManagement\SearchKeywordManagement
     */
    public function searchKeyword() {
        return $this->container()->get('lychee.module.content_management.search_keyword');
    }

    /**
     * @return \Lychee\Module\Recommendation\ColumnManagement
     */
    public function recommendationColumn() {
        return $this->container()->get('lychee.module.recommendation_column');
    }

    /**
     * @return \Lychee\Module\Post\StickyService
     */
    public function postSticky() {
        return $this->container()->get('lychee.module.post.sticky');
    }

    /**
     * @return \Lychee\Module\ContentManagement\ExpressionManagement
     */
    public function expression() {
        return $this->container()->get('lychee.module.content_management.expression');
    }

    /**
     * @return \Lychee\Bundle\AdminBundle\EventListener\EventDispatcher
     */
    public function adminEventDispatcher() {
        return $this->container()->get('lychee_admin.event_dispatcher');
    }

	/**
	 * @return LiveService
	 */
    public function live() {
    	return $this->container()->get('lychee.module.live');
    }

    /**
     * @return \Lychee\Module\Game\GameCategoryManager
     */
    public function gameCategory() {
        return $this->container()->get('lychee.module.game_category');
    }

    /**
     * @return \Lychee\Module\Game\GameColumnsManager
     */
    public function gameColumns() {
        return $this->container()->get('lychee.module.game_columns');
    }

    /**
     * @return \Lychee\Module\Game\GameColumnsRecommendationManager
     */
    public function gameColumnsRecommendation() {
        return $this->container()->get('lychee.module.game_columns_recommendation');
    }

    /**
     * @return \Lychee\Module\ContentManagement\ReviewStateService
     */
    public function gameReviewState() {
        return $this->container()->get('lychee.module.content_management.review_state');
    }

    /**
     * @return \Lychee\Module\ContentManagement\AndroidAutoUpdateManager
     */
    public function androidAutoUpdate() {
        return $this->container()->get('lychee.module.content_management.android_auto_update');
    }

	/**
	 * @return CiyoCoinPurchaseRecorder
	 */
    public function ciyoCoinPurchaseRecorder() {
    	return $this->container()->get('lychee.module.payment.ciyocoin_purchase_recorder');
	}


	/**
	 * @return PushSettingManager
	 */
	public function pushSettingManager() {
    	return $this->container()->get('lychee.module.notification.push_setting');
	}

	/**
	 * @return ExtraMessageService
	 */
	public function extraMessageService() {
    	return $this->container()->get('lychee.module.extramessage');
	}

    /**
     * @return EMUserService
     */
    public function emUerService() {
        return $this->container()->get('lychee.module.emuser');
    }

    /**
     * @return EMUser
     */
    public function emUser() {
        return $this->container()->get('lychee.module.emuser');
    }

    /**
     * @return EMPictureRecordService
     */
    public function emPictureRecord() {
        return $this->container()->get('lychee.module.empicturerecord');
    }

	/**
	 * @return CaituService
	 */
	public function caituservice() {
		return $this->container()->get('lychee.module.caituservice');
	}

	/**
	 * @return EMAuthenticationService
	 */
	public function emAuthenticationService() {
		return $this->container()->get('lychee.module.emauthentication');
	}

	/**
	 * @return PaymentIAPRefundManager
	 */
	public function iapRefundManager() {
		return $this->container()->get('lychee.module.payment.iap_refund_manager');
	}

	/**
	 * @return PurchaseRecorder
	 */
	public function purchaseRecorder() {
		return $this->container()->get('lychee.module.payment.purchase_recorder');
	}

	/**
	 * @return ProductManager
	 */
	public function productManager() {
		return $this->container()->get('lychee.module.payment.product_manager');
	}

    /**
     * @return BGM
     */
    public function ugsvBGM() {
        return $this->container()->get('lychee.module.ugsv.bgm');
    }

    /**
     * @return WhiteList
     */
    public function ugsvWhiteList() {
        return $this->container()->get('lychee.module.ugsv.whitelist');
    }

} 