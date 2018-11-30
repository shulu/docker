<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 7/1/15
 * Time: 5:24 PM
 */
namespace Lychee\Module\Notification\Task;

use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Foundation\ImageUtility;
use Lychee\Component\Task\Task;
use Lychee\Module\Notification\Entity\OfficialNotification;
use Lychee\Module\Notification\Entity\OfficialNotificationPush;
use Lychee\Module\Post\PostAnnotation;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use JPush\Model;

class pushOfficialNotification implements Task {

    use ContainerAwareTrait;
    use ModuleAwareTrait;

    public function getName() {
        return 'push official notification';
    }

    public function getDefaultInterval() {
        return 300;
    }

    public function run() {
        /**
         * @var \Doctrine\ORM\EntityManager $entityManager
         */
        $entityManager = $this->container()->get('doctrine')->getManager();
        $query = $entityManager->getRepository(OfficialNotificationPush::class)->createQueryBuilder('p')
            ->where('p.pushed < 2 AND p.pushTime <= :pushTime')
            ->setParameter('pushTime', new \DateTime())
            ->getQuery();
        $result = $query->getResult();
        /**
         * @var \JPush\JPushClient $jpushClient
         */
        $jpushClient = $this->container()->get('jpush.client');
        /**
         * @var \Lychee\Module\Notification\Entity\OfficialNotificationPush $row
         */
        foreach ($result as $row) {
            $pushPayload = $jpushClient->push();
            $tagsArr = json_decode($row->tags);
            if (is_array($tagsArr) && count($tagsArr) > 0) {
                if ($row->nextPushTime <= new \DateTime()) {
                    $tags = [];
                    for ($i = 0; $i < 3; $i++) {
                        $tag = array_shift($tagsArr);
                        if (null === $tag) {
                            break;
                        }
                        $tags[] = $tag;
                    }
                    if (count($tags) <= 0) {
                        continue;
                    }
                    $pushPayload->setAudience(Model\tag($tags));
                    if (count($tagsArr) > 0) {
                        $row->pushed = 1;
                        $row->tags = json_encode($tagsArr);
                        $row->nextPushTime = clone $row->nextPushTime->modify('+5 min');
                    } else {
                        $row->tags = null;
                        $row->pushed = 2;
                    }
                } else {
                    continue;
                }
            } else {
                $pushPayload->setAudience(Model\all);
                $row->pushed = 2;
            }
            /**
             * @var \Lychee\Module\Notification\Entity\OfficialNotification $officialNotification
             */
            $officialNotification = $entityManager->getRepository(OfficialNotification::class)
                ->find($row->notificationId);
            if (null === $officialNotification || $officialNotification->type == OfficialNotification::TYPE_COMMENT) {
                continue;
            }
            $extras = ['type' => 'promotion'];
            $targetId = '' . $officialNotification->targetId;
            switch ($officialNotification->type) {
                case OfficialNotification::TYPE_POST:
                    $extras = array_merge($extras, ['pid' => $targetId]);
	                $post = $this->post()->fetchOne($targetId);
	                if ($post) {
		                $annotation = json_decode($post->annotation, true);
		                if (isset($annotation[PostAnnotation::MULTI_PHOTOS])) {
		                	$multiPhotos = $annotation[PostAnnotation::MULTI_PHOTOS];
			                if (!empty($multiPhotos)) {
			                	$extras = array_merge($extras, ['image' => $multiPhotos[0]]);
			                }
		                }
	                }
                    break;
                case OfficialNotification::TYPE_TOPIC:
                    $extras = array_merge($extras, ['tid' => $targetId]);
                    break;
                case OfficialNotification::TYPE_USER:
                    $extras = array_merge($extras, ['uid' => $targetId]);
                    break;
                case OfficialNotification::TYPE_SUBJECT:
                    $extras = array_merge($extras, ['sid' => $targetId]);
                    break;
                case OfficialNotification::TYPE_LIVE:
                    $extras = array_merge($extras, ['live_uid' => $targetId]);
                    break;
                case OfficialNotification::TYPE_SITE:
                    $extras = [
                        'type' => 'web',
                        'url' => $this->parseUrl($officialNotification->url),
                    ];
                    break;
                default:
                    $extras = null;
            }

            $message = $row->message;
            if (!$message) {
                continue;
            }
            if (mb_strlen($message, 'utf8') > 20) {
                $message = mb_substr($message, 0, 20, 'utf8') . '...';
            }
            $androidMessage = Model\android($message, null, null, $extras);
            $iosMessage = Model\ios($message, 'default', null, null, $extras);
            $notification = Model\notification($message, $iosMessage, $androidMessage);
            switch ($row->platform) {
                case 0:
                    $pushPayload->setPlatform(Model\all);
                    break;
                case 1:
                    $pushPayload->setPlatform('ios');
                    break;
                case 2:
                    $pushPayload->setPlatform('android');
                    break;
            }
//            $pushPayload->setAudience(Model\alias(array('31724')));
//            $pushPayload->setAudience(Model\all);
            $pushPayload->setNotification($notification);
            $pushPayload->setOptions(Model\options(null, null, null, true));
            try {
                $pushPayload->send();
            } catch (\Exception $e) {

            } finally {
                $entityManager->flush();
            }
        }
    }

    /**
     * @param $schema
     * @return null|string
     */
    private function parseUrl($schema) {
        if (preg_match('/url=(.+)$/', $schema, $matches)) {
            return urldecode($matches[1]);
        }

        return null;
    }
}