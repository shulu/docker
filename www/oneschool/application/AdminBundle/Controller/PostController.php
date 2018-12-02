<?php

namespace Lychee\Bundle\AdminBundle\Controller;

use GuzzleHttp\Client;
use Lychee\Bundle\AdminBundle\Entity\OperationAccount;
use Lychee\Bundle\ApiBundle\Error\CommonError;
use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Component\Foundation\ImageUtility;
use Lychee\Component\Storage\Resource;
use Lychee\Component\Storage\StorageException;
use Lychee\Module\Post\PostAnnotation;
use Lychee\Module\Post\PostParameter;
use Lychee\Module\Topic\TopicParameter;
use Lychee\Module\Voting\Entity\VotingOption;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Lychee\Module\Voting\VotingService;
use Lychee\Component\IdGenerator\IdGenerator;

/**
 * Class PostController
 * @package Lychee\Bundle\AdminBundle\Controller
 * @Route("/post")
 */
class PostController extends BaseController
{
    public function getTitle()
    {
        return '快速发帖';
    }

    /**
     * @Route("/")
     * @Template()
     * @return array
     */
    public function indexAction()
    {
        // TODO: It Should be Optimized.
        $operationAccounts = $this->getUser()->operationAccounts;
        $accountIds = array();
        foreach ($operationAccounts as $account) {
            $accountIds[] = $account->id;
        }
        $accountsInfo = $this->account()->fetch($accountIds);
        $accounts = array();
        foreach ($accountsInfo as $accountInfo) {
            $accounts[] = array(
                'id' => $accountInfo->id,
                'nickname' => $accountInfo->nickname
            );
        }

        return $this->response($this->getTitle(), array(
            'accounts' => $accounts,
        ));
    }

    /**
     * @Route("/create")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createAction(Request $request)
    {
        $postType = $request->request->get('post_type', Post::TYPE_NORMAL);
        $accountId = $request->request->get('account_id');
        $topicName = $request->request->get('topic');
        $content = $request->request->get('content');
        $resourceUrl = $request->request->get('resource_url');
        $images = $request->files->get('pictures');
        if (count($images) > 9) {
            return $this->redirectErrorPage('图片不能超过9张.', $request);
        }
        if (!($topicId = $this->getTopicId($topicName))) {
            // Create topic
            $p = new TopicParameter();
            $p->creatorId = $accountId;
            $p->title = $topicName;
            $topic = $this->topic()->create($p);
            $topicId = $topic->id;
        }
        if (!$accountId) {
            throw $this->createNotFoundException('Account Not Found.');
        }
        if ($resourceUrl) {
            if (!preg_match('/^http[s]{0,1}:\/\//', $resourceUrl)) {
                return $this->redirect($this->generateUrl('lychee_admin_error', [
                    'errorMsg' => '资源链接不正确',
                    'callbackUrl' => $request->headers->get('referer'),
                ]));
            }
        }
        $parameter = new PostParameter();
        switch ($postType) {
            case Post::TYPE_RESOURCE:
                $parameter = $this->createResourcePostParams($request);
                break;
            case Post::TYPE_GROUP_CHAT:
                try {
                    $parameter = $this->createGroupchatPostParams($request, $accountId, $topicId);
                } catch (\Exception $e) {
                    $this->redirect($this->generateUrl('lychee_admin_error', [
                        'errorMsg' => $e->getMessage(),
                        'callbackUrl' => $request->headers->get('referer'),
                    ]));
                }
                break;
            case Post::TYPE_VOTING:
                try {
                    $parameter = $this->createVotingPostParams($request);
                } catch (\Exception $e) {
                    $this->redirect($this->generateUrl('lychee_admin_error', [
                        'errorMsg' => $e->getMessage(),
                        'callbackUrl' => $request->headers->get('referer'),
                    ]));
                }
                break;
            case Post::TYPE_SCHEDULE:
                $parameter = $this->createSchedulePostParams($request, $accountId, $topicId);
                break;
            case Post::TYPE_VIDEO:
                $parameter = $this->createVideoPostParams($request);
                break;
            default: // 图文帖
                $parameter = new PostParameter();
                $parameter->setAnnotation($this->generateAnnotation($request, $parameter));
        }

        $parameter->setAuthorId($accountId)
            ->setTopicId($topicId)
            ->setContent($content)
            ->setType((int)$postType);

        $post = $this->post()->create($parameter);

        return $this->redirect($this->generateUrl('lychee_admin_inquiry_postdetail', ['id' => $post->id]));
    }

    /**
     * @param $topicName
     * @return null
     */
    private function getTopicId($topicName)
    {
        if (!$topicName) {
            return null;
        }

        $topics = $this->topic()->fetchOneByTitle($topicName);
        if ($topics === null) {
            return null;
        }

        return $topics->id;
    }

    /**
     * @Route("/cache")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function cacheAction(Request $request)
    {
        if (null === $request->request->get('account_id')) {
            throw $this->createNotFoundException('Operation account Not Found.');
        }
        $previewId = uniqid();
        $session = new Session();
        $session->set('post_preview_' . $previewId, $request->request);

        if ($picLink = $request->request->get('pic_link')) {
            $session->set('post_preview_image_' . $previewId, $picLink);
        }
        if (true === $request->files->has('image')) {
            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $session->set('post_preview_image_size_' . $previewId, getimagesize($imageFile));
                $session->set('post_preview_image_' . $previewId, $this->upload()->saveImage($imageFile));
            }
        }

        return $this->redirect($this->generateUrl('lychee_admin_post_preview', array(
            'id' => $previewId,
        )));
    }

    /**
     * @Route("/preview/{id}")
     * @Template
     * @param $id
     * @return array
     */
    public function previewAction($id)
    {
        $session = new Session();
        $post = $session->get('post_preview_' . $id);
        $image = $session->get('post_preview_image_' . $id);
        $account = $this->account()->fetchOne($post->get('account_id'));

        return $this->response('帖子预览', array(
            'previewId' => $id,
            'post' => $post,
            'account' => $account,
            'image' => $image,
        ));
    }

    private function compressImg($imgFile, $imgType) {
        if ($imgType === IMAGETYPE_JPEG) {
            $im = imagecreatefromjpeg($imgFile);
        } elseif ($imgType === IMAGETYPE_PNG) {
            $im = imagecreatefrompng($imgFile);
        } else {
            return false;
        }
        $fileName = tempnam(sys_get_temp_dir(), '');
        imagejpeg($im, $fileName, 80);
        $compressImgUrl = $this->storage()->put($fileName);
        @unlink($fileName);

        return $compressImgUrl;
    }

    /**
     * 生成包含图片的annotation
     * @param Request $request
     * @param PostParameter $parameter
     * @param array $misc
     * @return mixed
     */
    private function generateAnnotation(Request $request, PostParameter &$parameter, $misc = []) {
        $pictures = $request->files->get('pictures');
        $annotation = [];
        $imageUrl = null;
        $HDPictureUrls = [];
        $pictureUrls = [];
        $imgTypeArray = [];
        $gifIndex= [];
        foreach ($pictures as $pic) {
            if ($pic) {
                $imgType = exif_imagetype($pic);
                if ($imgType === IMAGETYPE_JPEG || $imgType === IMAGETYPE_PNG) {
	                $img = ImageUtility::compressImg($this->storage(), $pic, $imgType);
                    if (false !== $img) {
                        $HDPictureUrls[] = $this->storage()->put($pic);
                        $pictureUrls[] = $img;
                        $imgTypeArray[] = $imgType;
                    }
                } elseif ($imgType === IMAGETYPE_GIF) {
                	$img = $this->storage()->put($pic);
                    $pictureUrls[] = $img;
	                $HDPictureUrls[] = $img;
                    $imgTypeArray[] = $imgType;
                }

            }
        }
        if (count($imgTypeArray)) {
            foreach ($imgTypeArray as $index => $value) {
                if ($value === 1) {
                    $gifIndex[] = $index;
                }
            } 
        }
        $picCount = count($pictureUrls);
        $imageUrl = null;
        if ($picCount > 0) {
            foreach ($pictures as $picture) {
                list($width[], $height[]) = getimagesize($picture);
            }
            if ($picCount === 1) {
                $annotation = PostAnnotation::setSinglePhoto(
                    isset($HDPictureUrls[0])? $HDPictureUrls[0]:null,
                    $pictureUrls[0],
                    $width,
                    $height,
                    $gifIndex
                );
            } else {
                $annotation = PostAnnotation::setMultiPhotos($HDPictureUrls, $pictureUrls, $width, $height, $gifIndex);
            }
            $imageUrl = $pictureUrls[0];
        }
        $videoUrl = null;
        if ($request->request->get('video_url')) {
            $videoUrl = $request->request->get('video_url');
        }
        $parameter->setResource($imageUrl, $videoUrl);

        $annotation = array_merge($annotation, $misc);
        if (is_array($annotation) && !empty($annotation)) {
            
            return json_encode($annotation);
        } else {
            return null;
        }
    }

    private function createResourcePostParams(Request $request) {
        $parameter = new PostParameter();
        $resourceUrl = $request->request->get('resource_url');
        $images = $request->files->get('pictures');
        $resourceImage = '';
        if (!empty($images) && isset($images[0]) && $images[0]) {
            $img = $images[0];
            $imgType = exif_imagetype($img);
            if ($imgType === IMAGETYPE_PNG || $imgType === IMAGETYPE_JPEG) {
	            $resourceImage = ImageUtility::compressImg($this->storage(), $img, $imgType);
            }
        }
        do {
            if ($resourceUrl) {
                $client = new Client();
                try {
                    $res = $client->get($resourceUrl);
                    if ($res->getStatusCode() == 200) {
                        $html = $res->getBody()->getContents();
                        $crawler = new Crawler($html);
                        $titleNode = $crawler->filter('head > title');
                        if ($titleNode->count()) {
                            $title = $titleNode->first()->text();
                            if ($title) {
                                $resourceTitle = $title;
                                break;
                            }
                        }
                    }
                } catch (\Exception $e) {

                }
            }
            $resourceTitle = '';
        } while (0);
        $resourceAnnotation = PostAnnotation::setResource($resourceUrl, $resourceTitle, $resourceImage);
        $parameter->setAnnotation($this->generateAnnotation($request, $parameter, $resourceAnnotation));

        return $parameter;
    }

    private function createGroupchatPostParams(Request $request, $authorId, $topicId) {
        $parameter = new PostParameter();
        $content = $request->request->get('content');
        $parameter->setAnnotation($this->generateAnnotation($request, $parameter));

        $chatGroupName = $request->request->get('im_group_name');
        if (mb_strlen($chatGroupName, 'utf8') > 20) {
            throw new \Exception('群聊组名不能多于20个字');
        }

        /** @var IdGenerator $idGenerator */
        $idGenerator = $this->get('lychee.module.post.id_generator');
        $postId = $idGenerator->generate();
        $parameter->setPostId($postId);

        /** @var GroupService $groupService */
        $groupService = $this->get('lychee.module.im.group');
        $group = $groupService->create(
            $authorId, $chatGroupName, null, $content, $topicId, $postId);
        if ($group == null) {
            throw new \Exception(CommonError::SystemBusy());
        }
        $parameter->setImGroupId($group->id);
        $parameter->setPostId($postId);

        return $parameter;
    }

    private function createVotingPostParams(Request $request) {
        $votingTitle = $request->request->get('voting_title');
        $content = $request->request->get('content');
        if (mb_strlen($votingTitle, 'utf8') > 60) {
            throw new \Exception('标题不能多于60个字');
        }
        $options = [];
        for ($i = 1; $i < 9; $i++) {
            $optionTitle = $request->request->get('opt_' . $i);
            if ($optionTitle) {
                $opt = new VotingOption();
                $opt->title = $optionTitle;
                $options[] = $opt;
            }
        }
        if (count($options) < 2) {
            throw new \Exception('投票选项必须多于2个');
        }
        /** @var IdGenerator $idGenerator */
        $idGenerator = $this->get('lychee.module.post.id_generator');
        $postId = $idGenerator->generate();
        /** @var VotingService $votingService */
        $votingService = $this->get('lychee.module.voting');
        $voting = $votingService->create($postId, $votingTitle, $content, $options);

        $parameter = new PostParameter();
        $parameter->setVotingId($voting->id);
        $parameter->setPostId($postId);
        $parameter->setAnnotation($this->generateAnnotation($request, $parameter));

        return $parameter;
    }
    
    private function createSchedulePostParams(Request $request, $authorId, $topicId) {
        $scheduleTitle = $request->request->get('schedule_title');
        $scheduleDesc = $request->request->get('content');
        $scheduleAddress = $request->request->get('schedule_address');
        $scheduleStartTime = $request->request->get('start_time');
        $scheduleEndTime = $request->request->get('end_time');
	    /** @var IdGenerator $idGenerator */
	    $idGenerator = $this->get('lychee.module.post.id_generator');
        $postId = $idGenerator->generate();
        /**
         * @var \Lychee\Module\Schedule\ScheduleService $scheduleService
         */
        $scheduleService = $this->get('lychee.module.schedule');
        $schedule = $scheduleService->create($authorId, $topicId, $postId,
            $scheduleTitle, $scheduleDesc, $scheduleAddress, null, null, null,
            new \DateTime($scheduleStartTime), new \DateTime($scheduleEndTime));
        $scheduleService->join($authorId, $schedule->id);

        $parameter = new PostParameter();
        $parameter->setScheduleId($schedule->id);
        $parameter->setPostId($postId);
        $parameter->setAnnotation($this->generateAnnotation($request, $parameter));

        return $parameter;
    }

    private function createVideoPostParams(Request $request) {
        $videoCover = $request->request->get('video_cover');
        $newVideoCover = trim($request->request->get('new_video_cover', ''));
        if ($newVideoCover) {
            $videoCover = $newVideoCover;
        }
        if ($videoCover) {
            list($width, $height) = getimagesize($videoCover);
            $misc = [
                'video_cover' => $videoCover,
                'video_cover_width' => $width,
                'video_cover_height' => $height,
            ];
        } else {
            $misc = [];
        }
        $parameter = new PostParameter();
        $parameter->setAnnotation($this->generateAnnotation($request, $parameter, $misc));

        return $parameter;
    }

    /**
     * @Route("/fetch_video_cover")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function fetchVideoCover(Request $request) {
        $url = $request->request->get('url');
        $coverUrl = $this->contentManagement()->fetchVideoCover($url);

        return new JsonResponse([
            'img' => $coverUrl
        ]);
    }

    /**
     * @Route("/video_cover/upload")
     * @Method("POST")
     * @param Request $request
     * @return Response
     */
    public function changeVideoCover(Request $request) {
        $file = $request->files->get('qqfile');
        try {
            $image = $this->storage()->put($file);
        } catch (StorageException $e) {
            return new Response(json_encode([
                'error' => $e->getMessage(),
            ]));
        }

        return new Response(json_encode([
            'success' => true,
            'image' => $image
        ]));
    }

}
