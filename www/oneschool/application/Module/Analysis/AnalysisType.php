<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14/11/11
 * Time: 下午4:30
 */

namespace Lychee\Module\Analysis;


/**
 * Class AnalysisType
 * @package Lychee\Module\Analysis
 */
class AnalysisType {

    const TOPIC = 'topic';

    const POST = 'post';

    const CHARACTER_COMMENT = 'character_comment';

    const IMAGE_COMMENT = 'image_comment';

    const USER = 'user';

    const POST_LIKE = 'post_like';

    const COMMENT_LIKE = 'comment_like';

    const FOLLOWING = 'following';

    const CONTENT_CONTRIBUTION = 'content_contribution';

    const ACTIVE_USERS = 'active_users';

    const IMAGE_INCREMENT = 'image_increment';

    const CHAT_MESSAGE = 'chat_message';

    const TOPIC_VIEWS = 'topic_views';

    /**
     * 80%用户流量集中在多少个最多人访问的次元中
     */
    const TOPIC_VISITOR = 'topic_visitor';

    const TOPIC_FOLLOWING = 'topic_following';

    /**
     * 充值人数
     */
    const RECHARGE_USER = 'recharge_user';

    /**
     *充值人次
     */
    const RECHARGE_TIMES = 'recharge_times';

    /**
     *充值收入
     */
    const RECHARGE_INCOME = 'recharge_income';
} 