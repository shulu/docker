<?php
namespace Lychee\Bundle\CoreBundle\Controller;

use Lychee\Bundle\CoreBundle\ComponentAwareTrait;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Lychee\Module\Authentication\AuthenticationService;
use Lychee\Module\Account\AccountService;
use Lychee\Module\Relation\RelationService;
use Lychee\Module\Post\PostService;
use Lychee\Module\Topic\TopicService;
use Lychee\Module\Comment\CommentService;
use Lychee\Module\Like\LikeService;
use Lychee\Module\Notification\OfficialNotificationService;
use Lychee\Module\Activity\ActivityService;
use Lychee\Module\Recommendation\RecommendationService;
use Lychee\Module\Upload\UploadService;

class Controller extends BaseController {

	/**
	 * 特殊的公共次元
	 */
	const SPECIAL_TOPIC = 53057;

    use ModuleAwareTrait, ComponentAwareTrait {
        ModuleAwareTrait::container insteadof ComponentAwareTrait;
    }
}