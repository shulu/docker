<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 9/20/16
 * Time: 6:28 PM
 */

namespace Lychee\Module\Notification\Task;


use Doctrine\ORM\EntityManager;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Task\Task;
use Lychee\Module\Notification\Entity\OfficialNotification;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class OfficialNotificationViewsTask implements Task {

	use ContainerAwareTrait;
	use ModuleAwareTrait;

	public function getName() {
		return 'calculate-official-notification-views';
	}

	public function getDefaultInterval() {
		return 3600 * 8;
	}

	public function run() {
		$tbName = 'ciyocon_oss.event_official_notification_view';
		/** @var EntityManager $em */
		$em = $this->container()->get('doctrine')->getManager();
		$conn = $em->getConnection();
		$stmt = $conn->prepare(
			"SELECT DISTINCT(notification_id)
			FROM $tbName"
		);
		$stmt->execute();
		$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		if ($result) {
			$notificationIds = array_map(function($item) {
				return $item['notification_id'];
			}, $result);

			$notificationIdsStr = implode(',', $notificationIds);
			$stmt = $conn->prepare(
				"SELECT notification_id, COUNT(id) notification_views
				FROM $tbName
				WHERE notification_id IN ($notificationIdsStr)
				GROUP BY notification_id"
			);
			$stmt->execute();
			$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			$views = array_reduce($result, function($result, $item) {
				$result[$item['notification_id']] = $item['notification_views'];

				return $result;
			});

			$stmt = $conn->prepare(
				"SELECT tb.notification_id, COUNT(tb.user_id) unique_views
				FROM (SELECT notification_id, user_id
				FROM $tbName
				WHERE notification_id IN ($notificationIdsStr)
				GROUP BY notification_id, user_id) tb
				GROUP BY tb.notification_id"
			);
			$stmt->execute();
			$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			$uniqueViews = array_reduce($result, function($result, $item) {
				$result[$item['notification_id']] = $item['unique_views'];

				return $result;
			});

			$officialNotifications = $em->getRepository(OfficialNotification::class)->findBy([
				'id' => $notificationIds
			]);
			if ($officialNotifications) {
				foreach ($officialNotifications as $notification) {
					/** @var OfficialNotification $notification */
					$notification->views = $notification->views + $views[$notification->id];
					$notification->uniqueViews = $notification->uniqueViews + $uniqueViews[$notification->id];
					$em->flush($notification);
				}
			}
			$conn->exec("DELETE FROM $tbName WHERE notification_id IN ($notificationIdsStr)");
		}

	}
}