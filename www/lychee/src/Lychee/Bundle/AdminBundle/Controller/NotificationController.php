<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 6/29/15
 * Time: 2:23 PM
 */

namespace Lychee\Bundle\AdminBundle\Controller;


use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Foundation\ImageUtility;
use Lychee\Module\Notification\Entity\OfficialNotification;
use Lychee\Module\Notification\Entity\OfficialNotificationPush;
use Lychee\Module\Post\PostAnnotation;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class NotificationController
 * @package Lychee\Bundle\AdminBundle\Controller
 * @Route("/notification")
 */
class NotificationController extends BaseController {

    /**
     * @return string
     */
    public function getTitle() {
        return '通知管理';
    }

    /**
     * @Route("/")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function indexAction() {
        return $this->redirect($this->generateUrl('lychee_admin_notification_official'));
    }

    /**
     * @Route("/official")
     * @Template
     * @param Request $request
     * @return array
     */
    public function officialAction(Request $request) {
        $cursor = $request->query->get('cursor', 0);
        $data = $this->officialNotification()->fetchAllOfficials($cursor, 50, $nextCursor);
        for ($i = 0, $len = count($data); $i < $len; $i++) {
            $data[$i]->image = ImageUtility::resize($data[$i]->image, 150, 999);
            switch ($data[$i]->type) {
                case 1:
                    $data[$i]->type = '帖子';
                    break;
                case 2:
                    $data[$i]->type = $this->getParameter('topic_name');
                    break;
                case 3:
                    $data[$i]->type = '用户';
                    break;
                case 4:
                    $data[$i]->type = '评论';
                    break;
                case 5:
                    $data[$i]->type = '网页';
                    $schema = $data[$i]->url;
                    if (preg_match('/url=(.+)$/', $schema, $matches)) {
                        $data[$i]->url = urldecode($matches[1]);
                    }
                    break;
                case 6:
                    $data[$i]->type = '专题';
                    break;
                case 7:
                    $data[$i]->type = '直播';
                    break;
            }
        }
        $officialNotificationTypes = $this->getNotificationTypes();
        $operationAccountIds = $this->get('lychee_admin.service.operation_accounts')->fetchIds();
        $operationAccountIds = array_reduce($operationAccountIds, function($result, $item) {
            $result[] = $item['id'];
            return $result;
        });
        $operationAccounts = $this->account()->fetch($operationAccountIds);
        $officialNotificationIds = array_map(function ($item) {
            return $item->id;
        }, $data);
        $pushData = $this->officialNotification()->fetchOfficialNotificationPush($officialNotificationIds);

        return $this->response('官方通知', [
            'previousCursor' => $request->query->get('previousCursor'),
            'nextCursor' => $nextCursor,
            'officialNotificationTypes' => $officialNotificationTypes,
            'operationAccounts' => $operationAccounts,
            'data' => $data,
            'pushData' => $pushData,
        ]);
    }

    /**
     * @return array
     */
    private function getNotificationTypes() {
        $reflection = new \ReflectionClass(OfficialNotification::class);

        return $reflection->getConstants();
    }

    /**
     * @Route("/official/create")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createOfficialNotificationAction(Request $request) {
        $sender = $request->request->get('sender');
        $message = $request->request->get('message');
        $type = $request->request->get('type');
        $targetId = trim($request->request->get('target_id'));
        $url = $request->request->get('url');
        $image = $request->files->get('image');
        $isPush = $request->request->get('push');
        $pushTime = new \DateTime($request->request->get('push_time'));
        if (!file_exists($image)) {
            $image = $this->getOfficialNotificationImage($type, $targetId);
        } else {
            $image = $this->storage()->put($image);
        }
        $now = new \DateTime();
        if ($isPush) {
            $publishTime = $pushTime;
        } else {
            $publishTime = $now;
        }
        switch($type) {
            case OfficialNotification::TYPE_POST:
                $function = 'addOfficialPost';
                break;
            case OfficialNotification::TYPE_USER:
                $function = 'addOfficialUser';
                break;
            case OfficialNotification::TYPE_TOPIC:
                $function = 'addOfficialTopic';
                break;
            case OfficialNotification::TYPE_SUBJECT:
                $function = 'addOfficialSubject';
                break;
            case OfficialNotification::TYPE_SITE:
                $notification = $this->officialNotification()->addOfficialSite(
                    $sender,
                    $now,
                    $message,
                    $image,
                    $url,
                    $publishTime
                );
                break;
            case OfficialNotification::TYPE_LIVE:
                $function = 'addOfficialLive';
                break;
            default:
                throw new \UnexpectedValueException('unknown official notification type');
        }
        if (!isset($notification)) {
            $notification = $this->officialNotification()->$function(
                $sender,
                $now,
                $message,
                $image,
                $targetId,
                $publishTime
            );
        }
        if ($isPush) {
            $pushPlatform = $request->request->get('push_platform');
            $platform = 0;
            if ($pushPlatform) {
                if (count($pushPlatform) === 1 && $pushPlatform[0] == 1) {
                    $platform = 1;
                } elseif (count($pushPlatform) === 1 && $pushPlatform[0] == 2) {
                    $platform = 2;
                }
            }
            $groups = [];
            for ($i = 0; $i < 12; $i++) {
                $groups[] = '_g' . $i;
            }
            $this->officialNotification()->setOfficialNotificationPush(
                $notification->id,
                $request->request->get('push_message'),
                $pushTime,
                $platform,
                json_encode($groups)
            );
        }

        return $this->redirect($this->generateUrl('lychee_admin_notification_official'));
    }

    /**
     * @param $type
     * @param $id
     * @return mixed|null|string
     */
    private function getOfficialNotificationImage($type, $id) {
        if ($type == OfficialNotification::TYPE_POST) {
            $post = $this->post()->fetchOne($id);
            if ($post) {
                if ($post->type === Post::TYPE_VIDEO) {
                    $annotation = json_decode($post->annotation, true);
                    if ($annotation) {
                        return $annotation[PostAnnotation::RESOURCE_VIDEO_COVER];
                    }
                }
                if ($post->imageUrl) {
                	return $post->imageUrl;
                } else {
                	$annotation = json_decode($post->annotation, true);
	                if ($annotation) {
	                	if (isset($annotation[PostAnnotation::MULTI_PHOTOS])) {
	                		$photos = $annotation[PostAnnotation::MULTI_PHOTOS];
			                if (!empty($photos)) {
			                	return $photos[0];
			                }
		                }
	                }
                }
	            return null;
            }
        } elseif ($type == OfficialNotification::TYPE_TOPIC) {
            $topic = $this->topic()->fetchOne($id);
            if ($topic) {
                return $topic->indexImageUrl;
            }
        } elseif ($type == OfficialNotification::TYPE_USER) {
            $user = $this->account()->fetchOne($id);
            if ($user) {
                return $user->avatarUrl;
            }
        } elseif ($type == OfficialNotification::TYPE_SUBJECT) {
            $subject = $this->specialSubject()->fetchOne($id);
            if ($subject) {
                return $subject->getBanner();
            }
        }

        return null;
    }

    /**
     * @Route("/official/remove")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeOfficialAction(Request $request) {
        $id = $request->request->get('id');
        $notifications = $this->officialNotification()->fetchOfficialsByIds([$id]);
        $em = $this->getDoctrine()->getManager();
        foreach ($notifications as $notification) {
            $em->remove($notification);
        }
        $pushInfos = $this->officialNotification()->fetchOfficialNotificationPush([$id]);
        foreach ($pushInfos as $info) {
            $em->remove($info);
        }
        $em->flush();

        return $this->redirect($this->generateUrl('lychee_admin_notification_official'));
    }
}