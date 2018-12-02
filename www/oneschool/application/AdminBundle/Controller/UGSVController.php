<?php

namespace Lychee\Bundle\AdminBundle\Controller;

use Lychee\Bundle\AdminBundle\Components\Foundation\Paginator;
use Lychee\Module\UGSV\BGMParameter;
use Lychee\Component\Foundation\ImageUtility;
use Lychee\Component\Foundation\FileUtility;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Lychee\Component\Foundation\ArrayUtility;

/**
 * Class UGSVController
 * @package Lychee\Bundle\AdminBundle\Controller
 * @Route("/ugsv")
 */
class UGSVController extends BaseController
{

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return '短视频管理';
    }

    /**
     * @Route("/")
     * @Template
     * @param Request $request
     * @return mixed
     */
    public function indexAction(Request $request)
    {
        return $this->redirect($this->generateUrl('lychee_admin_ugsv_videos'));
    }

    /**
     * @Route("/bgms")
     * @Method("GET")
     * @Template
     * @param Request $request
     * @return mixed
     */
    public function bgmsAction(Request $request)
    {
        $query = $request->query->get('query');
        $paginator = null;
        $cursor = $request->query->getInt('cursor', 0);
        if (0 >= $cursor) {
            $cursor = 0;
        }
        $page = $request->query->getInt('page', 1);
        $step=100;
        $firstSortNum=($page-1)*20+1;
        $keyword = null;
        if ($query) {
            $keyword = $query;
        }
        $ugsvbgmService = $this->ugsvBGM();
        $iterator = $ugsvbgmService->iterateForPager($keyword);
        $paginator = new Paginator($iterator);
        $paginator->setCursor($cursor)
            ->setPage($page)
            ->setStep($step)
            ->setStartPageNum($request->query->getInt('start_page', 1));
        $items = $paginator->getResult();

        $bgmIds = [];
        foreach ($items as $key => $item) {
            $bgmIds[] = $item->id;
        }
        $bgmUseCountings = $ugsvbgmService->fetchUseCountings($bgmIds);
        foreach ($items as $key => $item) {
            $item->duration = vsprintf("%02d:%02d", array($item->duration/60, $item->duration%60));
            $item->size = FileUtility::formatSize($item->size);
            if (isset($bgmUseCountings[$item->id])) {
                $item->useCount =  $bgmUseCountings[$item->id];
            }
            $items[$key] = $item;
        }

        return $this->response('背景音乐管理', array(
            'paginator' => $paginator,
            'items' => $items,
            'query' => $query,
            'firstSortNum' => $firstSortNum,
        ));
    }


    private function errTips($errorMsg, $request) {
        return $this->redirect($this->generateUrl('lychee_admin_error', [
            'errorMsg' => $errorMsg,
            'callbackUrl' => $request->headers->get('referer'),
        ]));
    }


    /**
     * @Route("/bgm/create")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createBgmAction(Request $request) {
        $bgmId = $request->request->get('id');
        $bgmId = intval($bgmId);

        $bgmName = $request->request->get('name');
        $bgmSingerName = $request->request->get('singer_name');
        $duration = $request->request->get('duration');

        $bgmName = trim($bgmName);
        if (empty(strlen($bgmName))) {
            return $this->errTips('必须填写歌曲名', $request);
        }
        $bgmSingerName = trim($bgmSingerName);
        if (empty(strlen($bgmSingerName))) {
            return $this->errTips('必须填写作者名', $request);
        }

        if (is_array($duration)
            &&$duration) {
            $duration = intval($duration[0])*60+intval($duration[1]);
        } else {
            $duration = 0;
        }

        if ($duration<=0) {
            return $this->errTips('必须填写歌曲长度', $request);
        }

        $cover = null;
        if ($request->files->has('cover')) {
            $imageFile = $request->files->get('cover');
            if (file_exists($imageFile)) {
                $cover = $this->storage()->setPrefix('ugsvbgm/')->put($imageFile);
            }
        }
        if (empty($cover)&&empty($bgmId)) {
            return $this->errTips('必须传封面图', $request);
        }

        $src = null;
        $srcSize = 0;
        if ($request->files->has('src')) {
            $file = $request->files->get('src');
            if (file_exists($file)) {
                $srcSize = $file->getSize();
                $src = $this->storage()->setPrefix('ugsvbgm/')->put($file);
            }
        }
        if (empty($src)&&empty($bgmId)) {
            return $this->errTips('必须传音频文件', $request);
        }

        $logger = $this->get('logger');
        $logger->info('debug bgm create '.json_encode([$bgmId, $bgmName, $bgmSingerName, $duration, $cover, $src, $srcSize]));

        $p = new BGMParameter();
        $p->name = $bgmName;
        $p->singerName = $bgmSingerName;
        $p->duration = $duration;
        $p->cover = $cover;
        $p->src = $src;
        $p->size = $srcSize;
        $bgmService = $this->ugsvBGM();
        if (empty($bgmId)) {
            $bgmService->create($p);
            return $this->redirect($request->headers->get('referer'));
        }
        $p->id=$bgmId;
        $bgmService->update($p);
        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/bgm/fetch")
     * @Method("GET")
     * @Template
     * @param Request $request
     * @return mixed
     */
    public function fetchBgmAction(Request $request) {
        $id = $request->query->getInt('id');
        if (empty($id)) {
            throw $this->createNotFoundException('BGM Not Found.');
        }
        $bgm = $this->ugsvBGM()->fetchOne($id);
        if (null === $bgm) {
            throw $this->createNotFoundException('BGM Not Found.');
        }
        $bgm->cover = ImageUtility::resize($bgm->cover, 320, 9999);
        return new JsonResponse($bgm);
    }

    /**
     * @Route("/bgm/top")
     * @Method("POST")
     * @param Request $request
     * @return mixed
     */
    public function topBgmAction(Request $request) {
        $id = $request->request->get('id');
        $logger = $this->get('logger');
        $logger->info('debug bgm top '.$id);
        $this->ugsvBGM()->topWeight($id);
        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/bgm/bottom")
     * @Method("POST")
     * @param Request $request
     * @return mixed
     */
    public function bottomBgmAction(Request $request) {
        $id = $request->request->get('id');
        $logger = $this->get('logger');
        $logger->info('debug bgm bottom '.$id);
        $this->ugsvBGM()->bottomWeight($id);
        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/bgm/switch")
     * @Method("POST")
     * @param Request $request
     * @return mixed
     */
    public function switchBgmAction(Request $request) {
        $ids = $request->request->get('ids');
        $logger = $this->get('logger');
        $logger->info('debug bgm switch '.json_encode($ids));
        $this->ugsvBGM()->switchWeight($ids[0], $ids[1]);
        return new JsonResponse(['result'=>true]);
    }

    /**
     * @Route("/bgm/remove")
     * @Method("POST")
     * @param Request $request
     * @return mixed
     */
    public function removeBgmAction(Request $request) {
        $id = $request->request->getInt('id');
        $logger = $this->get('logger');
        $logger->info('debug bgm remove '.$id);
        $this->ugsvBGM()->remove($id);
        return new JsonResponse(['result'=>true]);
    }


    /**
     * @Route("/whitelist")
     * @Method("GET")
     * @Template
     * @param Request $request
     * @return mixed
     */
    public function whiteListAction(Request $request) {
        $query = $request->query->get('query');
        $paginator = null;
        $cursor = $request->query->getInt('cursor', 0);
        if (0 >= $cursor) {
            $cursor = PHP_INT_MAX;
        }
        $page = $request->query->getInt('page', 1);
        $step=10;
        $keyword = null;
        if ($query) {
            $keyword = $query;
        }
        $iterator = $this->ugsvWhiteList()->iterateForPager($keyword);
        $paginator = new Paginator($iterator);
        $paginator->setCursor($cursor)
            ->setPage($page)
            ->setStep($step)
            ->setStartPageNum($request->query->getInt('start_page', 1));
        $res = $paginator->getResult();

        $userIds = [];
        foreach ($res as $key => $item) {
            $userIds[] = $item['userId'];
        }

        $userCountings = $this->account()->fetchCountings($userIds);
        $relationCounter = $this->relation()->buildRelationCounter($userIds);
        $lastPubTimes = $this->post()->fetchLastPubTime($userIds);
        $svCountings = $this->post()->fetchShortVideoCountings($userIds);

        $items = [];
        foreach ($res as $key => $val) {

            $userCounting = null;
            if (isset($userCountings[$val['userId']])) {
                $userCounting = $userCountings[$val['userId']];
            }

            $item = [];
            $item['userId'] = $val['userId'];
            $item['avatarUrl'] = $val['avatarUrl'];
            $item['nickname'] = $val['nickname'];
            $item['lastPostTime'] = isset($lastPubTimes[$val['userId']])?$lastPubTimes[$val['userId']]:'未发过帖';
            $item['postCount'] = $userCounting?$userCounting->postCount:0;
            $item['svCount'] = isset($svCountings[$item['userId']])?$svCountings[$item['userId']]:0;
            $item['fansCount'] = $relationCounter->countFollowers($val['userId']);
            $items[$key] = $item;
        }

        return $this->response('白名单管理', array(
            'paginator' => $paginator,
            'items' => $items,
            'query' => $query,
        ));
    }

    /**
     * @Route("/whitelist/create")
     * @Method("POST")
     * @Template
     * @param Request $request
     * @return mixed
     */
    public function createWhiteListAction(Request $request) {
        $ids = $request->request->get('ids');
        $ids = explode("\n", $ids);
        foreach ($ids as $key => $item) {
            $item = trim($item);
            if (!is_numeric($item)) {
                unset($ids[$key]);
                continue;
            }
            $item = intval($item);
            $ids[$key] = $item;
        }
        if (empty($ids)) {
            return $this->errTips('必须填用户id', $request);
        }

        $this->ugsvWhiteList()->create($ids);

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/whitelist/remove")
     * @Method("POST")
     * @param Request $request
     * @return mixed
     */
    public function removeWhiteListAction(Request $request) {
        $id = $request->request->getInt('id');
        $this->ugsvWhiteList()->remove($id);
        return new JsonResponse(['result'=>true]);
    }

    /**
     * @Route("/videos")
     * @Template
     * @param Request $request
     * @return array
     */
    public function videosAction(Request $request)
    {
        $query = $request->query->get('query');
        $cursor = $request->query->getInt('cursor', 0);
        if (0 >= $cursor) {
            $cursor = PHP_INT_MAX;
        }
        $page = $request->query->getInt('page', 1);
        $step = 20;
        $paginator = new Paginator($this->post()->iterateForShortVideoPager());
        $paginator->setCursor($cursor)
            ->setPage($page)
            ->setStep($step)
            ->setStartPageNum($request->query->get('start_page', 1));

        $list = $paginator->getResult();
        $postIds = array_keys(ArrayUtility::mapByColumn($list, 'postId'));
        $posts = $this->post()->fetch($postIds);
        foreach ($posts as $post) {
            $post->imageUrl = $this->getOriginalImage($post);
        }

        $details = $this->getDetailFrom($posts);

        return $this->response('短视频', array(
            'posts' => $posts,
            'authors' => $details['authors'],
            'topics' => $details['topics'],
            'list' => $list,
            'paginator' => $paginator,
            'query' => $query,
        ));
    }

    /**
     * @param $posts
     * @return array
     */
    private function getDetailFrom($posts)
    {
        $result = array_reduce($posts, function($result, $item) {
            if (false === isset($result['authors'])) {
                $result['authors'][] = $item->authorId;
            } else {
                if (false === in_array($item->authorId, $result['authors'])) {
                    $result['authors'][] = $item->authorId;
                }
            }
            if (false === isset($result['topics'])) {
                $result['topics'][] = $item->topicId;
            } else {
                if (false === in_array($item->topicId, $result['topics'])) {
                    $result['topics'][] = $item->topicId;
                }
            }
            $annotation = json_decode($item->annotation);
            if (isset($annotation->original_url) && $annotation->original_url) {
                $result['images'][$item->id] = $annotation->original_url;
            } else {
                $result['images'][$item->id] = $item->imageUrl;
            }

            return $result;
        });

        $authors = $this->account()->fetch($result['authors']);
        $topics = $this->topic()->fetch($result['topics']);

        return array(
            'authors' => $authors,
            'topics' => $topics,
        );
    }
}
