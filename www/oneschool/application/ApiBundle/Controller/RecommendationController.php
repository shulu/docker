<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Bundle\ApiBundle\DataSynthesizer\GameSynthesizer;
use Lychee\Bundle\ApiBundle\Error\CommonError;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Foundation\CursorWrapper;
use Lychee\Module\Game\Entity\Game;
use Lychee\Module\Recommendation\Post\Group;
use Lychee\Module\Recommendation\Post\GroupManager;
use Lychee\Module\Recommendation\Post\PredefineGroup;
use Lychee\Module\Recommendation\RecommendationType;
use Lychee\Module\Recommendation\UserRankingType;
use Lychee\Module\Topic\Exception\CategoryNotFoundException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\ExpressionLanguage\Tests\Node\Obj;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Lychee\Module\Recommendation\Entity\RecommendationItem;
use Lychee\Bundle\ApiBundle\DataSynthesizer\Synthesizer;
use Symfony\Component\HttpFoundation\Response;
use Lychee\Bundle\ApiBundle\DataSynthesizer\BannerSynthesizer;
use Lychee\Module\Recommendation\Entity\SpecialSubject;
use Lsw\MemcacheBundle\Cache\MemcacheInterface;
use Lychee\Module\Recommendation\ColumnManagement;
use Lychee\Module\Recommendation\Entity\Column;
use Lychee\Module\Recommendation\Entity\SubBanner;
use Lychee\Module\Recommendation\Entity\ColumnElement;
use Lychee\Module\Topic\TopicCategoryService;

class RecommendationController extends Controller {

    /**
     * @Route("/recommendation/topics/test")
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="zhai", "dataType"="integer", "required"=true},
     *     {"name"="meng", "dataType"="integer", "required"=true},
     *     {"name"="ran", "dataType"="integer", "required"=true},
     *     {"name"="fu", "dataType"="integer", "required"=true},
     *     {"name"="jian", "dataType"="integer", "required"=true},
     *     {"name"="ao", "dataType"="integer", "required"=true},
     *   }
     * )
     */
    public function topicsByTest(Request $request) {
        $account = $this->requireAuth($request);

        $hottestTopicIds = array(28711, 28960, 29520, 25159, 25098, 25386, 25384, 28880, 27531,
            25085, 31747, 29579, 25097, 25079, 28874, 25688, 25126, 25211, 28420, 25115);
        $hottestTopicIds = $this->filterBlockingTopics($request, $hottestTopicIds);
        $topicIdsToFollow = array_slice($hottestTopicIds, 0, 15);

        foreach ($topicIdsToFollow as $topicId) {
            $this->topicFollowing()->follow($account->id, $topicId);
        }

        $zhai = $request->query->getInt('zhai');
        $meng = $request->query->getInt('meng');
        $ran = $request->query->getInt('ran');
        $fu = $request->query->getInt('fu');
        $jian = $request->query->getInt('jian');
        $ao = $request->query->getInt('ao');

        $attributes = json_encode(array(
            'zhai' => $zhai, 'meng' => $meng, 'ran' => $ran,
            'fu' => $fu, 'jian' => $jian, 'ao' => $ao
        ));
        $userProfile = $this->account()->fetchOneUserProfile($account->id);
        $userProfile->attributes = $attributes;
        $this->account()->updateUserProfile($userProfile);

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildSimpleTopicSynthesizer($hottestTopicIds, $account->id);
        return $this->dataResponse($synthesizer->synthesizeAll());
    }

    /**
     * @Route("/recommendation/batch")
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false}
     *   }
     * )
     */
    public function getRecommendationBatch(Request $request) {
        return $this->dataResponse(array(
            'hot_topics' => array(),
            'hot_posts' => array(),
            'hot_image_comments' => array(),
            'editor_topics' => array(),
            'editor_users' => array(),
            'followed_ranking_users' => array(),
            'comment_ranking_users' => array(),
            'post_ranking_users' => array(),
            'image_comment_ranking_users' => array()
        ));
    }

    /**
     * @param string $type
     * @param int[] $ids
     * @param int $accountId
     *
     * @return Synthesizer
     */
    private function buildSynthesizer($type, $ids, $accountId) {
        $builder = $this->getSynthesizerBuilder();

        switch($type) {
            case RecommendationType::TOPIC:
                $method = 'buildTopicSynthesizer';
                break;
            case RecommendationType::POST:
                $method = 'buildListPostSynthesizer';
                break;
            case RecommendationType::COMMENT:
                $method = 'buildSimpleCommentSynthesizer';
                break;
            case RecommendationType::USER:
                $method = 'buildUserSynthesizer';
                break;
            case RecommendationType::VIDEO_POST:
                $method = 'buildListPostSynthesizer';
                break;
            default:
                throw new \InvalidArgumentException("unknown type {$type}");
        }
        return $builder->$method($ids, $accountId);
    }

    /**
     * @param string $type
     * @param RecommendationItem[] $items
     * @param int $accountId
     * @param Synthesizer|null $synthesizer
     *
     * @return array
     */
    private function synthesizeRecommendedItems($type, $items, $accountId, $synthesizer = null) {
        if ($synthesizer === null) {
            if (empty($items)) {
                return array();
            }
            $ids = array_map(function(/** @var RecommendationItem $item */$item){
                return $item->getTargetId();
            }, $items);

            $synthesizer = $this->buildSynthesizer($type, $ids, $accountId);
        }

        $result = array();
        foreach ($items as $item) {
            $itemData = $synthesizer->synthesizeOne($item->getTargetId());
            if ($itemData === null) {
                continue;
            }
            if ($item->getReason() !== null) {
                $itemData['reason'] = $item->getReason();
            }
            if ($item->getImage() !== null) {
                $itemData['preview_image'] = $item->getImage();
            }
            $result[] = $itemData;
        }

        return $result;
    }

    /**
     * @Route("/recommendation/{type}/hot", requirements={"type": "(topics|posts|users|video_posts)"})
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   description="获取热门推荐的内容",
     *   requirements={
     *     {"name"="type", "dataType"="string", "description"="可以为'topics','posts','users','video_posts'"}
     *   },
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="cursor", "dataType"="string", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function hottests(Request $request, $type) {
        $response = new JsonResponse();
        $this->setupLastModified($response, 'hots');
        if ($response->isNotModified($request)) {
            return $response;
        }

        $account = $this->getAuthUser($request);
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);
        $recommendationType = array(
            'topics' => RecommendationType::TOPIC,
            'posts' => RecommendationType::POST,
            'users' => RecommendationType::USER,
            'video_posts' => RecommendationType::VIDEO_POST,
        )[$type];

        if ($recommendationType == RecommendationType::VIDEO_POST) {
            $hottestIds = $this->getGroupPostsService()
                ->listPostIdsInGroup(PredefineGroup::ID_VIDEO, $cursor, $count, $nextCursor);
        } else {
            $hottestIds = $this->recommendation()->fetchHottestIds(
                $recommendationType, $cursor, $count, $nextCursor, array()
            );
        }

        if ($recommendationType == RecommendationType::TOPIC) {
            $hottestIds = $this->filterBlockingTopics($request, $hottestIds);
        }
        $synthesizer = $this->buildSynthesizer(
            $recommendationType, $hottestIds, $account ? $account->id : 0
        );

        if ($recommendationType == RecommendationType::POST || $recommendationType == RecommendationType::VIDEO_POST) {
            $result = $synthesizer->synthesizeAll();
            $result = array_values(array_filter($result, function($p){
                return !isset($p['deleted']) || $p['deleted'] == false;
            }));
        } else {
            $result = $synthesizer->synthesizeAll();
        }

        $typeStr = $type == 'video_posts' ? 'posts' : $type;
        $data = array($typeStr => $result, 'next_cursor' => strval($nextCursor));
        $response->setData($data);
        return $response;
    }

    /**
     * @Route("/recommendation/{type}/editor", requirements={"type": "(topics|users|posts)"})
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   description="获取编辑推荐的内容",
     *   requirements={
     *     {"name"="type", "dataType"="string", "description"="可以为'topics','users','posts'"}
     *   },
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="cursor", "dataType"="string", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function recommendedByEditor(Request $request, $type) {
        $account = $this->getAuthUser($request);
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);

        $recommendationType = array(
            'topics' => RecommendationType::TOPIC,
            'posts' => RecommendationType::POST,
            'users' => RecommendationType::USER,
        )[$type];

        $appVersion = $request->query->get(self::CLIENT_APP_VERSION_KEY);
        if (version_compare($appVersion, '2.4', '>=') && $recommendationType == RecommendationType::POST) {
            $postIds = $this->getGroupPostsService()
                ->listPostIdsInGroup(PredefineGroup::ID_INDEX, $cursor, $count, $nextCursor);

            $synthesizer = $this->getSynthesizerBuilder()->buildListPostSynthesizer($postIds, $account ? $account->id : 0);
            $result = $synthesizer->synthesizeAll();
            $result = array_values(array_filter($result, function($p){
                return !isset($p['deleted']) || $p['deleted'] == false;
            }));
            return $this->arrayResponse('posts', $result, $nextCursor);
        }

        $items = $this->recommendation()->listRecommendedItems(
            $recommendationType, $cursor, $count, $nextCursor
        );
        $result = $this->synthesizeRecommendedItems(
            $recommendationType, $items, $account ? $account->id : 0
        );
        $result = array_values(array_filter($result, function($p){
            return !isset($p['deleted']) || $p['deleted'] == false;
        }));

        return $this->arrayResponse(
            $type == 'image_comments' ? 'comments' : $type,
            $result,
            $nextCursor
        );
    }

    /**
     * @Route("/recommendation/topics")
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   description="获取客户端首页的推荐次元列表",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false}
     *   }
     * )
     */
    public function recommendedTopics(Request $request) {
        $account = $this->requireAuth($request);

        $items = $this->recommendation()->listRecommendedItems(
            RecommendationType::TOPIC, 0, 50, $nextCursor
        );
        $topicIds = array();
        foreach ($items as $item) {
            $topicIds[] = $item->getTargetId();
        }
        $synthesizer = $this->getSynthesizerBuilder()->buildSimpleTopicSynthesizer($topicIds, $account->id);
        return $this->dataResponse($synthesizer->synthesizeAll());
    }

	/**
	 * @Route("/recommendation/posts")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="recommendation",
	 *   description="获取24小时内的推荐帖子列表",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=false}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
    public function recommendedPosts(Request $request) {
	    $account = $this->getAuthUser($request);
	    $items = $this->recommendation()->fetchRecommendationPostsIn24();
	    $postIds = [];
	    foreach ($items as $item) {
	    	/** @var RecommendationItem $item */
	    	$postIds[] = $item->getTargetId();
	    }

	    $synthesizer = $this->getSynthesizerBuilder()->buildListPostSynthesizer($postIds, $account ? $account->id : 0);
	    $result = $synthesizer->synthesizeAll();
	    $result = array_values(array_filter($result, function($p){
		    return !isset($p['deleted']) || $p['deleted'] == false;
	    }));

	    $exposedPostIdsAndTopicIds = array();
	    foreach ($result as $p) {
		    if (isset($p['topic']['id'])) {
			    $exposedPostIdsAndTopicIds[] = [$p['id'], $p['topic']['id']];
		    }
	    }
	    $this->get('lychee.module.post.exposure_recorder')->recordPostsExposure($exposedPostIdsAndTopicIds);

	    return $this->dataResponse($result);
    }

    /**
     * @Route("/ranking/user/{type}", requirements={"type":"(followed|comment|post)"})
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   description="用户排行榜",
     *   requirements={
     *     {"name"="type", "dataType"="string", "description"="enum(followed,comment,post)"}
     *   },
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="cursor", "dataType"="string", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function userRanking(Request $request, $type) {
        return $this->arrayResponse('users', array(), 0);
    }

    /**
     * @Route("/recommendation/apps")
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false}
     *   }
     * )
     */
    public function appAction() {
        return $this->dataResponse(array(

        ));
    }

    /**
     * @Route("/recommendation/game/get")
     * @Method("GET")
     */
    public function gameAction(Request $request) {
        return $this->redirectToRoute('lychee_api_game_getgameweb', array('id' => $request->query->get('id', 0)));
    }

    /**
     * @Route("/recommendation/game/list")
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   description="获取推荐游戏列表",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="cursor", "dataType"="string", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function listGameAction(Request $request) {
        $account = $this->getAuthUser($request);
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);
        $clientPlatform = $request->query->get(self::CLIENT_PLATFORM_KEY);

        $items = $this->recommendation()->listRecommendedItems(
            RecommendationType::APP, $cursor, $count, $nextCursor
        );
        $gameIds = ArrayUtility::columns($items, 'targetId');
        $games = $this->game()->fetch($gameIds);
        $games = array_filter($games, function($g) use ($clientPlatform){
            /** @var Game $g */
            if ($clientPlatform == 'iphone' || $clientPlatform == 'ios') {
                return $g->getIosLink() != null;
            }
            if ($clientPlatform == 'android') {
                return $g->getAndroidLink() != null;
            }
            return false;
        });
        $synthesizer = new GameSynthesizer($games);
        return $this->arrayResponse('games', $synthesizer->synthesizeAll(), $nextCursor);
    }

    /**
     * @Route("/recommendation/web")
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false}
     *   }
     * )
     */
    public function webAction(Request $request) {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }

        $inReview = $request->query->getInt('inReview', 0) != 0;
        $userAgent = $request->headers->get('User-Agent');


        if (preg_match('/ciyocon\\/(\d+(?:\\.\d+(?:\\.\d+)?)?)/i', $userAgent, $matches)) {
            $version = $matches[1];
            if (version_compare($version, '1.5.6', '<=')) {
                return new Response('请更新你的次元社。');
            } else if (version_compare($version, '2.0', '<')) {
                $cacheKey = 'rec_web2';
                $useOldSite = true;
            } else if (version_compare($version, '2.2', '<')) {
                $cacheKey = 'rec_web';
                $useOldSite = false;
            } else {
                $cacheKey = 'rec_web2';
                $useOldSite = true;
            }
        } else {
            $cacheKey = 'rec_web2';
            $useOldSite = true;
        }

        /** @var MemcacheInterface $mc */
        $mc = $this->get('memcache.default');
        $webCache = $mc->get($cacheKey);
        if (!$inReview && $webCache !== false) {
            $response = new Response();
            $response->setPublic();
            $response->setMaxAge(60);
            if (is_array($webCache)) {
                list($cacheTime, $htmlCache) = $webCache;
                $cacheDateTime = new \DateTime($cacheTime);
                $cacheDateTime->setTimezone(new \DateTimeZone('UTC'));
                $response->setLastModified($cacheDateTime);
                if ($response->isNotModified($request)) {
                    return $response;
                } else {
                    $response->setContent($htmlCache);
                    return $response;
                }
            } else {
                $response->setContent($webCache);
                return $response;
            }
        }

        if ($useOldSite) {
            $html = $this->oldWebSite($request, $inReview);
        } else {
            $html = $this->newWebSite($request, $inReview);
        }

        $response = new Response($html);
        if (!$inReview) {
            $createTime = new \DateTime();
            $mc->set($cacheKey, array($createTime->format('Y-m-d H:i:s'), $html), 0, 3600 * 5);
            $response->setLastModified($createTime);
        }
        return $response;
    }

    private function getRecTopics($count, &$nextCursor) {
        $recTopics = $this->recommendation()->listRecommendedItems(
            RecommendationType::TOPIC, 0, $count, $nextCursor
        );
        return $this->synthesizeRecommendedItems(
            RecommendationType::TOPIC, $recTopics, 0
        );
    }

    private function getRecPosts($count, &$nextCursor) {
        $recPosts = $this->recommendation()->listRecommendedItems(
            RecommendationType::POST, 0, $count, $nextCursor
        );
        $nextCursor = $nextCursor === 0 ? null : $nextCursor;
        return $this->synthesizeRecommendedItems(
            RecommendationType::POST, $recPosts, 0
        );
    }

    public function oldWebSite(Request $request, $inReview) {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }

        $banners = $this->recommendationBanner()->fetchAvailableBanners();
        $bannerSynthesizer = new BannerSynthesizer($banners);

        $topics = $this->getRecTopics(3, $nextTopicCursor);
        $topics = $this->filterBlockingTopics($request, $topics);

        $posts = $this->getRecPosts(5, $postNextCursor);
        //过滤掉已经删除了的帖子
        $posts = array_filter($posts, function($post) {
            return isset($post['author']);
        });

        /** @var SubBanner[] $subBanners */
        $subBanners = $this->recommendationBanner()->fetchSubBanners(2);
        if ($inReview) {
            $subBanners = array_filter($subBanners, function($b){return $b->getType() != SubBanner::TYPE_GAME;});
        }

        /** @var ColumnManagement $columnManager */
        $columnManager = $this->get('lychee.module.recommendation_column');
        $columns = $columnManager->fetchPublishedColumns(Column::TYPE_POST);
        $elementsInColumns = array();
        $allPostIds = array();
        foreach ($columns as $column) {
            /** @var Column $column */
            $elements = $columnManager->fetchElements($column->getId(), 3);
            foreach ($elements as $element) {
                /** @var ColumnElement $element */
                if ($element->getElementId()) {
                    $allPostIds[] = $element->getElementId();
                }
            }
            $elementsInColumns[] = $elements;
        }
        $postSynthesizer = $this->getSynthesizerBuilder()->buildBasicPostSynthesizer($allPostIds, 0);

        $postsInColumns = array_map(function($elements)use($postSynthesizer) {
            $posts = array();
            foreach ($elements as $element) {
                /** @var ColumnElement $element */
                $post = $postSynthesizer->synthesizeOne($element->getElementId());
                $post['reason'] = $element->getRecommendationReason();
                if ($element->getImageUrl()) {
                    $post['image_url'] = $element->getImageUrl();
                }
                $posts[] = $post;
            }
            return $posts;
        }, $elementsInColumns);

        return $this->renderView('LycheeApiBundle:Recommendation:web2.html.twig', array(
            'banners' => $bannerSynthesizer->synthesizeAll(),
            'topics' => $topics,
            'posts' => $posts,
            'postsInColumns' => $postsInColumns,
            'columns' => $columns,
            'subbanners' => $subBanners,
            'subbanner_cursor' => 1,
            'post_cursor' => $postNextCursor,
            'in_review' => $inReview
        ));
    }

    public function newWebSite(Request $request, $inReview) {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }

        $banners = $this->recommendationBanner()->fetchAvailableBanners();
        $bannerSynthesizer = new BannerSynthesizer($banners);

        $topics = $this->getRecTopics(5, $nextTopicCursor);
        $topics = $this->filterBlockingTopics($request, $topics);

        /** @var SubBanner[] $subBanners */
        $subBanners = $this->recommendationBanner()->fetchSubBanners(5);
        if ($inReview) {
            $subBanners = array_filter($subBanners, function($b){return $b->getType() != SubBanner::TYPE_GAME;});
        }

        /** @var ColumnManagement $columnManager */
        $columnManager = $this->get('lychee.module.recommendation_column');
        $columns = $columnManager->fetchPublishedColumns(Column::TYPE_TOPIC);
        $elementsInColumns = array();
        $topicIdsInColumns = array();
        foreach ($columns as $column) {
            /** @var Column $column */
            $elements = $columnManager->fetchElements($column->getId(), 5);
            foreach ($elements as $element) {
                /** @var ColumnElement $element */
                if ($element->getElementId()) {
                    $topicIdsInColumns[] = $element->getElementId();
                }
            }
            $elementsInColumns[] = $elements;
        }
        $topicSynthesizer = $this->getSynthesizerBuilder()->buildTopicSynthesizer($topicIdsInColumns, 0);

        $topicsInColumns = array_map(function($elements)use($topicSynthesizer) {
            $topics = array();
            foreach ($elements as $element) {
                /** @var ColumnElement $element */
                $topic = $topicSynthesizer->synthesizeOne($element->getElementId());
                $topic['reason'] = $element->getRecommendationReason();
                if ($element->getImageUrl()) {
                    $topic['index_image'] = $element->getImageUrl();
                }
                $topics[] = $topic;
            }
            return $topics;
        }, $elementsInColumns);


        return $this->renderView('LycheeApiBundle:Recommendation/web:web.html.twig', array(
            'banners' => $bannerSynthesizer->synthesizeAll(),
            'topics' => $topics,
            'subbanners' => $subBanners,
            'columns' => $columns,
            'topicsInColumns' => $topicsInColumns,
        ));
    }

    /**
     * @Route("/recommendation/web/loadmore2")
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="post_cursor", "dataType"="integer", "required"=false},
     *     {"name"="post_index", "dataType"="integer", "required"=false},
     *     {"name"="subbanner_cursor", "dataType"="integer", "required"=false},
     *     {"name"="subbanner_index", "dataType"="integer", "required"=false},
     *     {"name"="inReview", "dataType"="integer", "required"=false}
     *   }
     * )
     */
    public function webLoadMore2Action(Request $request) {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }

        $inReview = $request->query->getInt('inReview', 0) != 0;

        $postCursor = $request->query->get('post_cursor');
        if ($postCursor !== null) {
            $recPosts = $this->recommendation()->listRecommendedItems(
                RecommendationType::POST, intval($postCursor), 10, $postNextCursor
            );
            $posts = $this->synthesizeRecommendedItems(
                RecommendationType::POST, $recPosts, 0
            );
            //过滤掉已经删除了的帖子
            $posts = array_filter($posts, function($post) {
                return isset($post['author']);
            });
            $postNextCursor = $postNextCursor === 0 ? null : $postNextCursor;
        } else {
            $posts = array();
        }

        $subbannerCursor = $request->query->get('subbanner_cursor');
        if ($subbannerCursor !== null) {
            /** @var SubBanner[] $subBanners */
            $subBanners = $this->recommendationBanner()->fetchSubBanners(2, $subbannerCursor + 1);
            $subbannerNextCursor = count($subBanners) < 2 ? null : $subbannerCursor + 1;
            if ($inReview) {
                $subBanners = array_filter($subBanners, function($b){return $b->getType() != SubBanner::TYPE_GAME;});
            }
        } else {
            $subBanners = array();
        }

        return $this->render('LycheeApiBundle:Recommendation:more2.html.twig', array(
            'posts' => $posts,
            'post_cursor' => isset($postNextCursor) ? $postNextCursor : null,
            'subbanners' => $subBanners,
            'subbanner_cursor' => isset($subbannerNextCursor) ? $subbannerNextCursor : null,
        ));
    }

    /**
     * @Route("/recommendation/special_subject/list")
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="cursor", "dataType"="string", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function listSpecialSubjectAction(Request $request) {
        $account = $this->getAuthUser($request);
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);
        /** @var SpecialSubject[] $subjects */
        $subjects = $this->specialSubject()->fetchByCursor($cursor, $count, $nextCursor);
        if (!$subjects) {
            $subjects = array();
        }

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildSimpleSpecialSubjectSynthesizer($subjects, $account ? $account->id : 0);

        return $this->arrayResponse('special_subjects', $synthesizer->synthesizeAll(), $nextCursor);
    }

    /**
     * @Route("/recommendation/special_subject/get")
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="id", "dataType"="integer", "required"=true},
     *   }
     * )
     */
    public function getSpecialSubjectAction(Request $request) {
        $account = $this->getAuthUser($request);
        $id = $this->requireId($request->query, 'id');
        $subject = $this->specialSubject()->fetchOne($id);
        if (!$subject) {
            return $this->errorsResponse(CommonError::ObjectNonExist($id));
        }

        $synthesizer = $this->getSynthesizerBuilder()
            ->buildSpecialSubjectSynthesizer(array($subject), $account ? $account->id : 0);

        $data = $synthesizer->synthesizeOne($id);
        $otherSubjects = array();
        if (($previous = $this->specialSubject()->fetchPrevious($id)) != null) {
            $otherSubjects[$previous->getId()] = $previous;
        }
        if (($next = $this->specialSubject()->fetchNext($id)) != null) {
            $otherSubjects[$next->getId()] = $next;
        }

        if (count($otherSubjects) > 0) {
            $otherSynthesizer = $this->getSynthesizerBuilder()
                ->buildSimpleSpecialSubjectSynthesizer($otherSubjects, $account ? $account->id : 0);

            if ($previous) {
                $data['previous'] = $otherSynthesizer->synthesizeOne($previous->getId());
            }
            if ($next) {
                $data['next'] = $otherSynthesizer->synthesizeOne($next->getId());
            }
        }

        return $this->dataResponse($data);
    }


    /**
     * @Route("/recommendation/column/{type}/list", requirements={"type":"(post|topic)"})
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   description="获取栏目里的帖子列表",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="column_id", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="string", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function listColumnElements(Request $request, $type) {
        $account = $this->requireAuth($request);
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);
        $columnId = $this->requireId($request->query, 'column_id');


        /** @var ColumnManagement $columnManager */
        $columnManager = $this->get('lychee.module.recommendation_column');
        /** @var Column $column */
        $column = $columnManager->fetchOneColumn($columnId);
        if ($column->getType() != $type) {
            return $this->arrayResponse($type.'s', array(), 0);
        }
        $elements = $columnManager->fetchElements($columnId, $count, $cursor + 1);
        if (count($elements) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $cursor + 1;
        }
        if (count($elements) == 0) {
            return $this->arrayResponse($type.'s', array(), 0);
        }

        $ids = array();
        foreach ($elements as $element) {
            /** @var ColumnElement $element */
            $ids[] = $element->getElementId();
        }

        $synthesizer = $this->buildSynthesizer($type, $ids, $account->id);

        $data = array();
        foreach ($elements as $element) {
            /** @var ColumnElement $element */
            $datum = $synthesizer->synthesizeOne($element->getElementId());
            $datum['reason'] = $element->getRecommendationReason();
            $data[] = $datum;
        }
        return $this->arrayResponse($type.'s', $data, $nextCursor);
    }

    /**
     * @Route("/recommendation/{type}/list_by_hot", requirements={"type":"(posts|topics)"})
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   description="获取某次元分类中的热门",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="category", "dataType"="string", "required"=true},
     *     {"name"="cursor", "dataType"="string", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function getHotsInCategory(Request $request, $type) {
        $response = new JsonResponse();
        $this->setupLastModified($response, 'hots');
        if ($response->isNotModified($request)) {
            return $response;
        }

        $account = $this->getAuthUser($request);

        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);
        $category = $this->requireParam($request->query, 'category');

        /** @var TopicCategoryService $categoryService */
        $categoryService = $this->get('lychee.module.topic.category');
        $categoryId = $categoryService->categoryIdOfName($category);

        if ($type == 'topics') {
            if ($categoryId == null) {
                return $this->arrayResponse('topics', array(), 0);
            }
            $topicIds = $this->recommendation()->listTopicIdsInCategoryByHotOrder(
                $categoryId, $cursor, $count, $nextCursor);

            $synthesizer = $this->getSynthesizerBuilder()->buildTopicSynthesizer($topicIds, $account ? $account->id : 0);

            $response->setData(array('topics' => $synthesizer->synthesizeAll(),
                'next_cursor' => strval($nextCursor)));
            return $response;
        } else {
            if ($categoryId == null) {
                return $this->arrayResponse('posts', array(), 0);
            }

            $categoryGroupMap = array(
                '兴趣' => PredefineGroup::ID_XINGQU,
                '活动' => PredefineGroup::ID_HUODONG,
                '动漫' => PredefineGroup::ID_DONGMAN,
                '游戏' => PredefineGroup::ID_YOUXI,
                '生活' => PredefineGroup::ID_SHENGHUO,
                '逗比' => PredefineGroup::ID_DOUBI,
                '社团' => PredefineGroup::ID_SHETUAN,
                '偶像' => PredefineGroup::ID_OUXIANG,
                '影视' => PredefineGroup::ID_YINGSHI,
            );

            if (!isset($categoryGroupMap[$category])) {
                return $this->arrayResponse('posts', array(), 0);
            }
            $groupId = $categoryGroupMap[$category];
            $postIds = $this->getGroupPostsService()
                ->listPostIdsInGroup($groupId, $cursor, $count, $nextCursor);

            $synthesizer = $this->getSynthesizerBuilder()->buildListPostSynthesizer($postIds, $account ? $account->id : 0);
            $result = $synthesizer->synthesizeAll();
            $result = array_values(array_filter($result, function($p){
                return !isset($p['deleted']) || $p['deleted'] == false;
            }));

            $response->setData(array('posts' => $result,
                'next_cursor' => strval($nextCursor)));
            return $response;
        }

    }

    /**
     * @Route("/recommendation/topics/hotInCategories")
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   description="获取全部次元分类中最热门的次元",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认1，最多不超过20"}
     *   }
     * )
     */
    public function getMostHotTopicInAllCategories(Request $request) {
        $account = $this->requireAuth($request);

        $response = new JsonResponse();
        $this->setupLastModified($response, 'hots');
        if ($response->isNotModified($request)) {
            return $response;
        }
        
        /** @var TopicCategoryService $categoryService */
        $categoryService = $this->get('lychee.module.topic.category');
        $categories = $categoryService->getCurrentCategories();

        $count = $request->query->getInt('count', 1);
        if ($count > 20) {
            $count = 20;
        }
        if ($count < 1) {
            $count = 1;
        }

        $allTopicIds = array();
        $topicIdsByCategoryIds = array();
        foreach ($categories as $category) {
            $topicIds = $this->recommendation()->listTopicIdsInCategoryByHotOrder(
                $category->id, 0, $count, $nextCursor);
            $allTopicIds = array_merge($allTopicIds, $topicIds);
            $topicIdsByCategoryIds[$category->id] = $topicIds;
        }

        $synthesizer = $this->getSynthesizerBuilder()->buildSimpleTopicSynthesizer($allTopicIds, $account->id);
        $data = array();
        foreach ($categories as $category) {
            $topicIds = $topicIdsByCategoryIds[$category->id];
            $topics = array();
            foreach ($topicIds as $topicId) {
                $topicData = $synthesizer->synthesizeOne($topicId);
                if ($topicData) {
                    $topics[] = $topicData;
                }
            }
            $data[] = array(
                'name' => $category->name,
                'topics' => $topics
            );
        }

        $response->setData($data);
        return $response;
    }

    /**
     * @Route("/recommendation/topics/editor/categories")
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   description="获取全部次元分类的编辑推荐次元",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true}
     *   }
     * )
     */
    public function getTopicsByEditorInAllCategories(Request $request) {
        $account = $this->requireAuth($request);
        /** @var TopicCategoryService $categoryService */
        $categoryService = $this->get('lychee.module.topic.category');
        $categories = $categoryService->getCurrentCategories();

        $topicIdsByCategoryIds = $this->recommendation()->fetchTopicsByEditorChoice();
        if (empty($topicIdsByCategoryIds)) {
            $topicIdsByCategoryIds = array(
                301 => array(25362, 25076, 33787, 25384, 25923, 25159),
                306 => array(29661, 31168, 25097),
                305 => array(25150, 25109, 27925),
                308 => array(25168, 27579, 33894),
                304 => array(26082, 30653, 25183),
                303 => array(25116, 25093, 26033)
            );
        }

        $allTopicIds = array();
        $categoryById = array();
        foreach ($categories as $category) {
            $topicIds = isset($topicIdsByCategoryIds[$category->id]) ? $topicIdsByCategoryIds[$category->id] : null;
            if (!empty($topicIds)) {
                $allTopicIds = array_merge($allTopicIds, $topicIds);
                $categoryById[$category->id] = $category;
            }
        }

        $synthesizer = $this->getSynthesizerBuilder()->buildSimpleTopicSynthesizer($allTopicIds, $account->id);
        $data = array();
        foreach ($topicIdsByCategoryIds as $categoryId => $topicIds) {
            $topics = array();
            foreach ($topicIds as $topicId) {
                $topicData = $synthesizer->synthesizeOne($topicId);
                if ($topicData) {
                    $topics[] = $topicData;
                }
            }
            $data[] = array(
                'name' => $categoryById[$categoryId]->name,
                'topics' => $topics
            );
        }

        return $this->dataResponse($data);
    }

    /**
     * @param Response $response
     * @param string $key
     */
    private function setupLastModified(Response $response, $key) {
        $llm = $this->get('lychee.module.recommendation.last_modified_manager');
        $lastModified = $llm->getLastModified($key);
        $response->setPublic();
        if ($lastModified != false) {
            $response->setLastModified($lastModified);
        }
    }

    /**
     * @Route("/recommendation/tabs")
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   description="获取推荐的tab列表",
     *   parameters={
     *      {"name"="in_review", "dataType"="integer", "required"=false, "description"="为1时不返回部分推荐"}
     *   }
     * )
     */
    public function getDisplayTabs(Request $request) {
    	$inReview = $request->query->get('in_review', 0);
        $appVersion = $request->query->get(self::CLIENT_APP_VERSION_KEY);
        $client = $request->query->get(self::CLIENT_PLATFORM_KEY, 'android');

        if ($appVersion && version_compare($appVersion, '2.5', '<')){
            $tabs = array(
                array('name' => '推荐'),
                array('name' => '视频'),
                array('name' => '兴趣'),
                array('name' => '动漫'),
                array('name' => '游戏'),
                array('name' => '生活'),
                array('name' => '逗比'),
                array('name' => '社团'),
                array('name' => '偶像'),
            );
        } else {
        	/** @var GroupManager $gm */
            $gm = $this->get('lychee.module.recommendation.group_manager');
            $groups = $gm->getGroupsToShow($appVersion, $inReview, $client);
            $tabs = array();

            foreach($groups as $group) {
                $tabs[] = array('name' => $group->name());
            }
//            array_splice($tabs, 1, 0, array(array('name' => '关注')));
        }

        return $this->dataResponse($tabs);
    }

    /**
     * @Route("/recommendation/tabs/posts")
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   description="获取指定tab的帖子列表",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="tab", "dataType"="string", "required"=true},
     *     {"name"="cursor", "dataType"="string", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function getTabPosts(Request $request) {
        $account = $this->getAuthUser($request);

        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);
        $groupName = $this->requireParam($request->query, 'tab');

        $response = new JsonResponse();

        $this->setupLastModified($response, 'hots');
        if ($response->isNotModified($request)) {
            return $response;
        }
        if ($groupName == '关注') {
            $postIds = array();
            $nextCursor = 0;
            if ($account) {
                $postIds = $this->getFolloweePostIds($account->id, $cursor, $count, $nextCursor);
            }
        } else {
            $groupManager = $this->get('lychee.module.recommendation.group_manager');
	        /** @var Group $group */
            $group = $groupManager->getGroupByName($groupName);
            if ($group == null) {
                return $this->arrayResponse('posts', array(), 0);
            }
            $client = $request->query->get(self::CLIENT_PLATFORM_KEY, 'android');
	        $appVersion = $request->query->get(self::CLIENT_APP_VERSION_KEY);
	        if (version_compare($appVersion, '2.6', '>=')) {
		        $postIds = $this->getGroupPostsService()->randomListPostIdsInGroupForClient($group->id(), $count, $nextCursor, $client);
	        } else {
		        $postIds = $this->getGroupPostsService()->listPostIdsInGroup(
			        $group->id(), $cursor, $count, $nextCursor);
	        }
        }

        $synthesizer = $this->getSynthesizerBuilder()->buildListPostSynthesizer($postIds, $account ? $account->id : 0);
        $result = $synthesizer->synthesizeAll();
        $result = array_values(array_filter($result, function($p){
            return !isset($p['deleted']) || $p['deleted'] == false;
        }));

        $exposedPostIdsAndTopicIds = array();
        foreach ($result as $p) {
            if (isset($p['topic']['id'])) {
                $exposedPostIdsAndTopicIds[] = [$p['id'], $p['topic']['id']];
            }
        }
        $this->get('lychee.module.post.exposure_recorder')->recordPostsExposure($exposedPostIdsAndTopicIds);

        $response->setData(array('posts' => $result,
            'next_cursor' => strval($nextCursor)));
        return $response;
    }

    private function getFolloweePostIds($userId, $cursor, $count, &$nextCursor) {
        $userIterator = new CursorWrapper(
            function($cursor, $count, &$nextCursor) use ($userId) {
                $followeeIds = $this->relation()->fetchFolloweeIdsByUserId(
                    $userId, $cursor, $count, $nextCursor
                );
                // 把自己加入"关注"人的帖子列表中
                $followeeIds[] = $userId;
                $followeeIds = array_unique($followeeIds);

                return $followeeIds;
            },
            100
        );
        return $this->post()->fetchPublicIdsByAuthorIds(
            $userIterator, $cursor, $count, $nextCursor
        );
    }

    /**
     * @Route("/recommendation/banners")
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   parameters={
     *     {"name"="device_resolution", "dataType"="string", "required"=false,
     *       "description"="屏幕分辨率, 形如{宽}x{高}的字符串。如，320x480。以像素为单位"}
     *   }
     * )
     */
    public function getBannersAction() {
        $banners = $this->recommendationBanner()->fetchAvailableBanners();
        $synthesizer = new BannerSynthesizer($banners);
        $result = $synthesizer->synthesizeAll();
        return $this->dataResponse($result);
    }

    /**
     * @return \Lychee\Module\Recommendation\Post\GroupPostsService
     */
    private function getGroupPostsService() {
        return $this->get('lychee.module.recommendation.group_posts');
    }

	/**
	 * @Route("/recommendation/getJingxuanLive")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="recommendation",
	 *   parameters={}
	 * )
	 *
	 * @return null|\Psr\Http\Message\StreamInterface
	 */
    public function getJingxuanLive() {
	    $liveInfo = $this->live()->getRecommendedLive();
	    $liveInfoCacheKey = 'liveInfo:' . $liveInfo->pizusId;
	    /** @var MemcacheInterface $memcacheService */
	    $memcacheService = $this->get('memcache.default');
	    $liveInfoCache = $memcacheService->get($liveInfoCacheKey);
	    if (!$liveInfoCache) {
		    $liveInfo = $this->live()->getPizusLiveInfo($liveInfo->pizusId);
		    do {
			    if ($liveInfo && isset($liveInfo['s'])) {
				    $status = $liveInfo['s'];
				    if ($status == 0 && isset($liveInfo['data'])) {
					    $data = $liveInfo['data'];
					    if ($data['living'] == 1) {
						    $response = [$liveInfo];
						    break;
					    }
				    }
			    }
			    $response = [];
		    } while (0);

		    $memcacheService->set($liveInfoCacheKey, $response, 0, 60);

		    return $this->dataResponse($response);
	    } else {
	    	return $this->dataResponse($liveInfoCache);
	    }
    }

    /**
     * @Route("/recommendation/getJingxuanInke")
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   parameters={}
     * )
     *
     * @return JsonResponse
     */
    public function getRecommendationLive() {
        $now = new \DateTime();
        $data = $this->live()->getOneJingxuanLiveByDate($now);
        if (!$data) {
            $data = new \stdClass();
        }
        return $this->dataResponse($data);
    }

	/**
	 * @Route("/recommendation/getVipPosts")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="recommendation",
	 *   description="获取VIP用户的帖子列表",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=false},
	 *     {"name"="cursor", "dataType"="string", "required"=false},
	 *     {"name"="count", "dataType"="integer", "required"=false,
	 *       "description"="每次返回的数据，默认20，最多不超过50"}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
    public function getVipPostsAction(Request $request) {
	    $account = $this->getAuthUser( $request );

	    list( $cursor, $count ) = $this->getCursorAndCount( $request->query, 20, 50 );
	    $vipIds  = $this->account()->fetchVips();
	    $postIds = $this->post()->fetchIdsByAuthorIds( $vipIds, $cursor, $count, $nextCursor );

	    $synthesizer = $this->getSynthesizerBuilder()->buildListPostSynthesizer( $postIds, $account ? $account->id : 0 );
	    $result      = $synthesizer->synthesizeAll();
	    $result      = array_values( array_filter( $result, function ( $p ) {
		    return ! isset( $p['deleted'] ) || $p['deleted'] == false;
	    } ) );

	    $response = new JsonResponse();
	    $response->setData( array(
		    'posts'       => $result,
		    'next_cursor' => strval( $nextCursor )
	    ) );

	    return $response;
    }
    
    /**
     * @Route("/vip_ranking")
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   parameters={
     *      {"name"="access_token", "dataType"="string", "required"=false}
     *   }
     * )
     */
    public function getVipRanking(Request $request) {
        /** @var MemcacheInterface $memcache */
        $memcache = $this->container()->get('memcache.default');
        $vip_users = $memcache->get('count_following');
        if(!$vip_users) {
            return new JsonResponse([]);
        }
        $account = $this->getAuthUser($request);
        $synthesizer = $this->buildSynthesizer(
            RecommendationType::USER, $vip_users, $account ? $account->id : 0
        );
        $list = $synthesizer->synthesizeAll();

        return new JsonResponse($list);
    }

    /**
     *
     * @Route("/recommendation/active_user")
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   parameters={
     *      {"name"="access_token", "dataType"="string", "required"=false}
     *   }
     * )
     *
     */
	public function getActiveUserRanking(Request $request) {

		//$account = $this->requireAuth( $request );
		/** @var MemcacheInterface $memcache */
		$memcache  = $this->container()->get( 'memcache.default' );
		$activeUsers = array_keys( $memcache->get( 'recommendActiveUser' ) );

		if(!$activeUsers) {
			return new JsonResponse([]);
		}

		$synthesizer = $this->buildSynthesizer(
			RecommendationType::USER, $activeUsers, 0
		);
		$list = $synthesizer->synthesizeAll();

		return new JsonResponse($list);
	}

	/**
	 *
	 * @Route("/recommendation/big_man")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="recommendation",
	 *   parameters={
	 *      {"name"="access_token", "dataType"="string", "required"=false}
	 *   }
	 * )
	 *
	 */
	public function getBigManRanking(Request $request) {

		//$account = $this->requireAuth( $request );
		/** @var MemcacheInterface $memcache */
		$memcache  = $this->container()->get( 'memcache.default' );
		$activeUsers = array_values( $memcache->get( 'recommendBigMan' ) );

		if(!$activeUsers) {
			return new JsonResponse([]);
		}

		$synthesizer = $this->buildSynthesizer(
			RecommendationType::USER, $activeUsers, 0
		);
		$list = $synthesizer->synthesizeAll();

		return new JsonResponse($list);
	}

	/**
	 *
	 * @Route("/recommendation/presenter")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="recommendation",
	 *   parameters={
	 *      {"name"="access_token", "dataType"="string", "required"=false}
	 *   }
	 * )
	 *
	 */
	public function getPresenterRanking(Request $request) {

		//$account = $this->requireAuth( $request );
		/** @var MemcacheInterface $memcache */
		$memcache  = $this->container()->get( 'memcache.default' );
		$activeUsers = array_values( $memcache->get( 'recommendPresenter' ) );

		if(!$activeUsers) {
			return new JsonResponse([]);
		}

		$synthesizer = $this->buildSynthesizer(
			RecommendationType::USER, $activeUsers, 0
		);
		$list = $synthesizer->synthesizeAll();

		return new JsonResponse($list);
	}

	/**
	 *
	 * @Route("/recommendation/lives")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="recommendation",
	 *   parameters={
	 *      {"name"="access_token", "dataType"="string", "required"=false}
	 *   }
	 * )
	 *
	 */
	public function getOpeningLives(Request $request) {

		$account = $this->requireAuth( $request );
		/** @var MemcacheInterface $memcache */

		$liveIds = $this->recommendation()->listRecommendationLiveIds();

		if(!$liveIds) {
			return new JsonResponse([]);
		}

		$synthesizer = $this->buildSynthesizer(
			RecommendationType::POST, $liveIds, $account ? $account->id : 0
		);
		$list = $synthesizer->synthesizeAll();

		return new JsonResponse($list);
	}





    /**
     *
     * ### 返回内容 ###
     *
     * ```json
     * {
     * "posts": [
     * {
     * "id": "135585057071105",
     * "topic": {
     * "id": "54723",
     * "create_time": 1531291443,
     * "title": "人人都是次元偶像",
     * "description": "拍摄你的吸pick小视频，在次元社出道吧！",
     * "index_image": "http://qn.ciyocon.com/upload/FkrCyqBxL26j0rSysSm7GY-vRSGR",
     * "post_count": 58,
     * "followers_count": 286,
     * "private": false,
     * "apply_to_follow": false,
     * "certified": true,
     * "following": false,
     * "manager": {
     * "id": "31721"
     * }
     * },
     * "create_time": 1533361586,
     * "type": "short_video",
     * "content": "【短视频】 传一下下视频",
     * "image_url": "http://qn.ciyocon.com/ugsvcover/f26fbb0d59c764f3d4c6c4e60e63c0de",
     * "video_url": "http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/4d5285545285890780903837409/J8kdsx9mClEA.mp4",
     * "annotation": {
     * "video_cover_width": 576,
     * "video_cover_height": 1024,
     * "video_cover": "http://qn.ciyocon.com/ugsvcover/f26fbb0d59c764f3d4c6c4e60e63c0de"
     * },
     * "author": {
     * "id": "515871",
     * "nickname": "你可以叫我葵哥",
     * "avatar_url": "http://qn.ciyocon.com/upload/FikT0FL-EmM0C9YJGN8t-piv4nN1",
     * "gender": "male",
     * "level": 57,
     * "signature": " c圈老咸鱼。\n热爱萌物和少女漫，只想暴富x欢迎前来勾搭\n那些奇奇怪怪的人就别来找我了，心情好就跟你讲两句道理拉黑举报，心情不好骂的狗血喷头然后举报拉黑。有评论看到会回复，爱你们",
     * "ciyoCoin": "0.00",
     * "phone": "",
     * "favourites_count": 0
     * },
     * "latest_likers": [],
     * "liked_count": 10,
     * "commented_count": 4,
     * "reposted_count": 0,
     * "liked": false,
     * "favorited": false
     * },
     * {
     * "id": "135488993649665",
     * "topic": {
     * "id": "25150",
     * "create_time": 1409036016,
     * "title": "自拍",
     * "description": "动作要快！姿势要帅！",
     * "index_image": "http://qn.ciyocon.com/upload/Ftukf1ei0TZCsBboXvZAQWQaXShz",
     * "cover_image": "",
     * "post_count": 84656,
     * "followers_count": 246527,
     * "private": false,
     * "apply_to_follow": false,
     * "color": "b56dd7",
     * "certified": false,
     * "following": false,
     * "manager": {
     * "id": "344274"
     * }
     * },
     * "create_time": 1533269973,
     * "type": "short_video",
     * "content": "【短视频】 燃烧我的卡路里\n减肥打卡",
     * "image_url": "http://qn.ciyocon.com/ugsvcover/3bdb8f491789faa68f636b485eae1870",
     * "video_url": "http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/43696eaa5285890780880103481/Mfre56qfyasA.mp4",
     * "annotation": {
     * "video_cover_width": 544,
     * "video_cover_height": 960,
     * "video_cover": "http://qn.ciyocon.com/ugsvcover/3bdb8f491789faa68f636b485eae1870"
     * },
     * "author": {
     * "id": "1172303",
     * "nickname": "怪化猫°",
     * "avatar_url": "http://qn.ciyocon.com/upload/FrIOVq_Y_oFW49UY4rTI2tkt8pbf",
     * "gender": "female",
     * "level": 60,
     * "signature": "八月 一定要好好努力啊…",
     * "ciyoCoin": "80.00",
     * "phone": "",
     * "certificate": "次元人气偶像Top1",
     * "favourites_count": 0
     * },
     * "latest_likers": [],
     * "liked_count": 38,
     * "commented_count": 1,
     * "reposted_count": 0,
     * "liked": false,
     * "favorited": false
     * }
     * ],
     * "next_cursor": "1662040"
     * }
     * ```
     *
     * @Route("/recommendation/posts/tab")
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   description="根据标签id获取指定tab的帖子列表",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="tab", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="string", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function getPostsByTabId(Request $request) {
        $account = $this->getAuthUser($request);

        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);
        $groupId = $this->requireParam($request->query, 'tab');

        $groupManager = $this->get('lychee.module.recommendation.group_manager');
        /** @var Group $group */
        $group = $groupManager->getGroupById($groupId);
        if ($group == null) {
            return $this->arrayResponse('posts', array(), 0);
        }
        $client = $request->query->get(self::CLIENT_PLATFORM_KEY, 'android');
        $postIds = $this->getGroupPostsService()->randomListPostIdsInGroupForClient($group->id(), $count, $nextCursor, $client);

        $synthesizer = $this->getSynthesizerBuilder()->buildListPostSynthesizer($postIds, $account ? $account->id : 0);
        $result = $synthesizer->synthesizeAll();
        $result = array_values(array_filter($result, function($p){
            return !isset($p['deleted']) || $p['deleted'] == false;
        }));

        $exposedPostIdsAndTopicIds = array();
        foreach ($result as $p) {
            if (isset($p['topic']['id'])) {
                $exposedPostIdsAndTopicIds[] = [$p['id'], $p['topic']['id']];
            }
        }
        $this->get('lychee.module.post.exposure_recorder')->recordPostsExposure($exposedPostIdsAndTopicIds);

        $response = $this->dataResponse(array('posts' => $result,
            'next_cursor' => strval($nextCursor)), true);
        return $response;
    }


    /**
     *
     * ### 返回内容 ###
     *
     * ```json
     * {
     * "posts": [
     * {
     * "id": "135585057071105",
     * "topic": {
     * "id": "54723",
     * "create_time": 1531291443,
     * "title": "人人都是次元偶像",
     * "description": "拍摄你的吸pick小视频，在次元社出道吧！",
     * "index_image": "http://qn.ciyocon.com/upload/FkrCyqBxL26j0rSysSm7GY-vRSGR",
     * "post_count": 58,
     * "followers_count": 286,
     * "private": false,
     * "apply_to_follow": false,
     * "certified": true,
     * "following": false,
     * "manager": {
     * "id": "31721"
     * }
     * },
     * "create_time": 1533361586,
     * "type": "short_video",
     * "content": "【短视频】 传一下下视频",
     * "image_url": "http://qn.ciyocon.com/ugsvcover/f26fbb0d59c764f3d4c6c4e60e63c0de",
     * "video_url": "http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/4d5285545285890780903837409/J8kdsx9mClEA.mp4",
     * "annotation": {
     * "video_cover_width": 576,
     * "video_cover_height": 1024,
     * "video_cover": "http://qn.ciyocon.com/ugsvcover/f26fbb0d59c764f3d4c6c4e60e63c0de"
     * },
     * "author": {
     * "id": "515871",
     * "nickname": "你可以叫我葵哥",
     * "avatar_url": "http://qn.ciyocon.com/upload/FikT0FL-EmM0C9YJGN8t-piv4nN1",
     * "gender": "male",
     * "level": 57,
     * "signature": " c圈老咸鱼。\n热爱萌物和少女漫，只想暴富x欢迎前来勾搭\n那些奇奇怪怪的人就别来找我了，心情好就跟你讲两句道理拉黑举报，心情不好骂的狗血喷头然后举报拉黑。有评论看到会回复，爱你们",
     * "ciyoCoin": "0.00",
     * "phone": "",
     * "favourites_count": 0
     * },
     * "latest_likers": [],
     * "liked_count": 10,
     * "commented_count": 4,
     * "reposted_count": 0,
     * "liked": false,
     * "favorited": false
     * },
     * {
     * "id": "135488993649665",
     * "topic": {
     * "id": "25150",
     * "create_time": 1409036016,
     * "title": "自拍",
     * "description": "动作要快！姿势要帅！",
     * "index_image": "http://qn.ciyocon.com/upload/Ftukf1ei0TZCsBboXvZAQWQaXShz",
     * "cover_image": "",
     * "post_count": 84656,
     * "followers_count": 246527,
     * "private": false,
     * "apply_to_follow": false,
     * "color": "b56dd7",
     * "certified": false,
     * "following": false,
     * "manager": {
     * "id": "344274"
     * }
     * },
     * "create_time": 1533269973,
     * "type": "short_video",
     * "content": "【短视频】 燃烧我的卡路里\n减肥打卡",
     * "image_url": "http://qn.ciyocon.com/ugsvcover/3bdb8f491789faa68f636b485eae1870",
     * "video_url": "http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/43696eaa5285890780880103481/Mfre56qfyasA.mp4",
     * "annotation": {
     * "video_cover_width": 544,
     * "video_cover_height": 960,
     * "video_cover": "http://qn.ciyocon.com/ugsvcover/3bdb8f491789faa68f636b485eae1870"
     * },
     * "author": {
     * "id": "1172303",
     * "nickname": "怪化猫°",
     * "avatar_url": "http://qn.ciyocon.com/upload/FrIOVq_Y_oFW49UY4rTI2tkt8pbf",
     * "gender": "female",
     * "level": 60,
     * "signature": "八月 一定要好好努力啊…",
     * "ciyoCoin": "80.00",
     * "phone": "",
     * "certificate": "次元人气偶像Top1",
     * "favourites_count": 0
     * },
     * "latest_likers": [],
     * "liked_count": 38,
     * "commented_count": 1,
     * "reposted_count": 0,
     * "liked": false,
     * "favorited": false
     * }
     * ],
     * "next_cursor": "1662040"
     * }
     * ```
     *
     * @Route("/recommendation/posts/jingxuan")
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   description="获取精选tab的帖子列表",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="cursor", "dataType"="string", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function getJingxuanPosts(Request $request) {
        $account = $this->getAuthUser($request);

        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);
        $groupId = PredefineGroup::ID_JINGXUAN;

        $client = $request->query->get(self::CLIENT_PLATFORM_KEY, 'android');
        $postIds = $this->getGroupPostsService()->randomListPostIdsInGroupForClient($groupId, $count, $nextCursor, $client);

        $synthesizer = $this->getSynthesizerBuilder()->buildListPostSynthesizer($postIds, $account ? $account->id : 0);
        $result = $synthesizer->synthesizeAll();
        $result = array_values(array_filter($result, function($p){
            return !isset($p['deleted']) || $p['deleted'] == false;
        }));

        $exposedPostIdsAndTopicIds = array();
        foreach ($result as $p) {
            if (isset($p['topic']['id'])) {
                $exposedPostIdsAndTopicIds[] = [$p['id'], $p['topic']['id']];
            }
        }
        $this->get('lychee.module.post.exposure_recorder')->recordPostsExposure($exposedPostIdsAndTopicIds);

        $response = $this->dataResponse(array('posts' => $result,
            'next_cursor' => strval($nextCursor)), true);
        return $response;
    }



    /**
     *
     * ### 返回内容 ###
     *
     * ```json
     * [
     * {
     * "id": 102,
     * "name": "萌妹"
     * },
     * {
     * "id": 312,
     * "name": "游戏"
     * }
     * ]
     *
     * ```
     *
     * @Route("/recommendation/tab/customs")
     * @Method("GET")
     * @ApiDoc(
     *   section="recommendation",
     *   description="获取推荐的自定义标签列表",
     *   parameters={
     *   }
     * )
     */
    public function getDisplayCustomTabs(Request $request) {
        $appVersion = $request->query->get(self::CLIENT_APP_VERSION_KEY);
        $client = $request->query->get(self::CLIENT_PLATFORM_KEY, 'android');
        /** @var GroupManager $gm */
        $gm = $this->get('lychee.module.recommendation.group_manager');
        $groups = $gm->getDisplayCustomGroups($appVersion, $client);
        $tabs = array();

        foreach($groups as $group) {
            $tabs[] = [
                'id' => $group->id(),
                'name' => $group->name(),
            ];
        }
        return $this->dataResponse($tabs);
    }

}
