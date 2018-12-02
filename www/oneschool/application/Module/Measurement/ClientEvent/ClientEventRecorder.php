<?php
namespace Lychee\Module\Measurement\ClientEvent;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManagerInterface;

class ClientEventRecorder {

    const PLATFORM_IOS = 1;
    const PLATFORM_ANDROID = 2;
    const PLATFORM_WEB = 3;

    /** @var EntityManagerInterface  */
    private $em;

    /**
     * ClientEventRecorder constructor.
     * @param RegistryInterface $doctrine
     */
    public function __construct($doctrine) {
        $this->em = $doctrine->getManager();
    }

    public function recordPostView($postId, $userId, $platformId) {
        $this->recordEvent('ciyocon_oss.event_post_view', 'post_id', $postId, $userId, 'platform', $platformId);
    }

    public function recordPostShare($postId, $userId) {
        $this->recordEvent('ciyocon_oss.event_post_share', 'post_id', $postId, $userId);
    }

    public function recordRecBannerView($bannerId, $userId) {
        $this->recordEvent('ciyocon_oss.event_rec_banner_view', 'banner_id', $bannerId, $userId);
    }

    public function recordGameBannerView($bannerId, $userId) {
        $this->recordEvent('ciyocon_oss.event_game_banner_view', 'banner_id', $bannerId, $userId);
    }

    public function recordOfficialNotificationView($notificationId, $userId) {
        $this->recordEvent('ciyocon_oss.event_official_notification_view', 'notification_id', $notificationId, $userId);
    }

    public function recordPromotionView($promotionId, $userId, $topicId) {
        $this->recordEvent('ciyocon_oss.event_promotion_view', 'promotion_id', $promotionId, $userId, 'topic_id', $topicId);
    }

    private function recordEvent($table, $subjectName, $subjectId, $userId, $infoName = null, $info = null) {
        if ($infoName != null && $info != null) {
            $sql = sprintf('INSERT INTO %s(time, %s, user_id, %s) VALUES(?, ?, ?, ?)', $table, $subjectName, $infoName);
            $this->em->getConnection()->executeUpdate($sql, array(time(), $subjectId, $userId, $info), array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
        } else {
            $sql = sprintf('INSERT INTO %s(time, %s, user_id) VALUES(?, ?, ?)', $table, $subjectName);
            $this->em->getConnection()->executeUpdate($sql, array(time(), $subjectId, $userId), array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT));
        }

    }
}