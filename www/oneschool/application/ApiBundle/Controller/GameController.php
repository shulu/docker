<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Bundle\ApiBundle\DataSynthesizer\GameCategorySynthesizer;
use Lychee\Bundle\ApiBundle\DataSynthesizer\GameColumnSynthesizer;
use Lychee\Bundle\ApiBundle\DataSynthesizer\GameSynthesizer;
use Lychee\Bundle\ApiBundle\Error\CommonError;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Recommendation\Entity\Banner;
use Lychee\Module\Game\Entity\Banner as GameBanner;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Lychee\Module\Game\Entity\Game;
use Symfony\Component\HttpFoundation\Request;
use Lychee\Bundle\ApiBundle\DataSynthesizer\BannerSynthesizer;
use Lychee\Module\Recommendation\RecommendationType;

class GameController extends Controller {

    /**
     * @Route("/game/banners")
     * @Method("GET")
     * @ApiDoc(
     *   section="game",
     *   parameters={
     *   }
     * )
     */
    public function getGameBannersAction(Request $request) {
        $bannerService = $this->get('lychee.module.game_banner');
        /** @var GameBanner[] $gameBanners */
        $gameBanners = $bannerService->fetchPublishedBanners();

        $banners = array();
        foreach ($gameBanners as $gb) {
            $b = new Banner();
            $b->id = $gb->id;
            $b->title = $gb->title;
            $b->description = $gb->description;
            $b->url = $gb->url;
            $b->imageUrl = $gb->imageUrl;
            $b->imageWidth = $gb->imageWidth;
            $b->imageHeight = $gb->imageHeight;
            $b->shareTitle = $gb->shareTitle;
            $b->shareText = $gb->shareText;
            $b->shareImageUrl = $gb->shareImageUrl;
            $b->shareBigImageUrl = $gb->shareBigImageUrl;
            $banners[] = $b;
        }

        $synthesizer = new BannerSynthesizer($banners);
        $result = $synthesizer->synthesizeAll();
        return $this->dataResponse($result);
    }

    /**
     * @Route("/game/topics")
     * @Method("GET")
     * @ApiDoc(
     *   section="game",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="cursor", "dataType"="string", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function getGameTopicsAction(Request $request) {
        $account = $this->getAuthUser($request);
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);

        $topicIds = $this->recommendation()->listTopicIdsInCategoryByHotOrder(304, $cursor, $count, $nextCursor);
        $synthesizer = $this->getSynthesizerBuilder()->buildSimpleTopicSynthesizer($topicIds, $account ? $account->id : 0);
        return $this->arrayResponse('topics', $synthesizer->synthesizeAll(), $nextCursor);
    }

    /**
     * @Route("/game/list/web")
     * @Method("GET")
     * @ApiDoc(
     *   section="game",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="cursor", "dataType"="string", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function getGameListWebAction(Request $request) {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }

        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);

        $clientPlatform = $request->query->get(self::CLIENT_PLATFORM_KEY);
        $platform = strtolower($clientPlatform) == 'android' ? 'android' : 'ios';

        $games = $this->game()->fetchByPlatform($platform, $cursor, $count, $nextCursor);

        return $this->render('LycheeApiBundle:App:list.html.twig', array(
            'apps' => $games
        ));
    }

    /**
     * @Route("/game/list")
     * @Method("GET")
     * @ApiDoc(
     *   section="game",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="cursor", "dataType"="string", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function getGameListAction(Request $request) {
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);

        $clientPlatform = $request->query->get(self::CLIENT_PLATFORM_KEY);
        $platform = strtolower($clientPlatform) == 'android' ? 'android' : 'ios';

        $games = $this->game()->fetchByPlatform($platform, $cursor, $count, $nextCursor);
        $synthesizer = new GameSynthesizer($games);
        return $this->arrayResponse('games', $synthesizer->synthesizeAll(), $nextCursor);
    }

    /**
     * @Route("/game/web")
     * @Method("GET")
     * @ApiDoc(
     *   section="game",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="id", "dataType"="integer", "required"=true},
     *   }
     * )
     */
    public function getGameWebAction(Request $request) {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }

        $id = $this->requireId($request->query, 'id');
        $apps = $this->game()->fetch(array($id));
        if (count($apps) == null) {
            throw $this->createNotFoundException();
        }
        /** @var Game $app */
        $app = current($apps);
        if ($app->getTopicId()) {
            $synthesizer = $this->getSynthesizerBuilder()->buildSimpleTopicSynthesizer(array($app->getTopicId()), 0);
            $topics = $synthesizer->synthesizeAll();
        } else {
            $topics = [];
        }

        $userAgent = $request->server->get('HTTP_USER_AGENT');
        if (preg_match('#ciyocon/([0-9\.]+)#', $userAgent, $matches)) {
            $androidOk = true;
            $showBack = false;
        } else {
            $androidOk = false;
            $showBack = true;
        }
        return $this->render('LycheeApiBundle:App:app.html.twig', array(
            'application' => $app,
            'screenshots' => json_decode($app->getAppScreenshots(), true),
            'topics' => $topics,
            'androidOk' => $androidOk,
            'showBack' => $showBack,
        ));
    }

    /**
     * @Route("/app/list")
     * @Method("GET")
     * @ApiDoc(
     *   section="app",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="cursor", "dataType"="string", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
    public function getListAction(Request $request) {
	    list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);

	    $clientPlatform = $request->query->get(self::CLIENT_PLATFORM_KEY);
	    $platform = strtolower($clientPlatform) == 'android' ? 'android' : 'ios';

	    $apps = $this->game()->fetchByPlatform($platform, $cursor, $count, $nextCursor);
	    $synthesizer = new GameSynthesizer($apps);
	    return $this->arrayResponse('apps', $synthesizer->synthesizeAll(), $nextCursor);
    }

    /**
     * @Route("/app/list/web")
     * @Method("GET")
     * @ApiDoc(
     *   section="app",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="cursor", "dataType"="string", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function getListWebAction(Request $request) {
        return $this->redirectToRoute('lychee_api_game_getgamelistweb');    }

    /**
     * @Route("/app/web")
     * @Method("GET")
     * @ApiDoc(
     *   section="app",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=false},
     *     {"name"="id", "dataType"="integer", "required"=true},
     *   }
     * )
     */
    public function getWebAction(Request $request) {
        return $this->redirectToRoute('lychee_api_game_getgameweb', array('id' => $request->query->get('id', 0)));
    }

	/**
	 * @Route("/app/categories")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="app",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=false},
	 *   }
	 * )
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
    public function getGameCategoriesAction() {
    	$categories = $this->game()->fetchGameCategories();
	    $synthesizer = new GameCategorySynthesizer($categories);

	    return $this->dataResponse([
	    	'game_categories' => $synthesizer->synthesizeAll(),
	    ]);
    }

	/**
	 * @Route("/app/column_recommendation")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="app",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=false},
	 *     {"name"="id", "dataType"="string", "required"=true},
	 *     {"name"="cursor", "dataType"="string", "required"=false},
	 *     {"name"="count", "dataType"="integer", "required"=false, "description"="每次返回的数据，默认20，最多不超过50"}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
    public function getGameColumnRecommendationAction(Request $request) {
	    $columnId = $this->requireId($request->query, 'id');
	    list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);
	    $clientPlatform = $request->query->get(self::CLIENT_PLATFORM_KEY);
	    $platform = strtolower($clientPlatform) == 'android' ? 'android' : 'ios';

	    $column = $this->game()->fetchGameColumn($columnId);
	    if (!$column) {
	    	return $this->errorsResponse(CommonError::ObjectNonExist($columnId));
	    }
	    $columns = ArrayUtility::mapByColumn([$column], 'id');
	    $gameIds = $this->game()->fetchColumnRecommendationList($columnId, $platform, $cursor, $count, $nextCursor);
	    $games = $this->game()->fetch($gameIds);
	    $gameSynthesizer = new GameSynthesizer($games);
	    $columnSynthesizer = new GameColumnSynthesizer($columns, $gameSynthesizer);

	    return $this->arrayResponse('column_recommendations', $columnSynthesizer->synthesizeOne($columnId), $nextCursor);
    }

	/**
	 * @Route("/app/list_by_category")
	 * @Method("GET")
	 * @ApiDoc(
	 *   section="app",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=false},
	 *     {"name"="id", "dataType"="string", "required"=true},
	 *     {"name"="cursor", "dataType"="string", "required"=false},
	 *     {"name"="count", "dataType"="integer", "required"=false, "description"="每次返回的数据，默认20，最多不超过50"}
	 *   }
	 * )
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
    public function getGameListByCatAction(Request $request) {
    	$catId = $this->requireId($request->query, 'id');
	    $clientPlatform = $request->query->get(self::CLIENT_PLATFORM_KEY);
	    $platform = strtolower($clientPlatform) == 'android' ? 'android' : 'ios';

	    list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);
	    $games = $this->game()->fetchGamesByCat($catId, $platform, $cursor, $count, $nextCursor);
	    $gameSynthesizer = new GameSynthesizer($games);

	    return $this->arrayResponse('apps', $gameSynthesizer->synthesizeAll(), $nextCursor);
    }

	/**
	 * @Route("/app/visit_game_detail")
	 * @Method("POST")
	 * @ApiDoc(
	 *   section="app",
	 *   parameters={
	 *     {"name"="access_token", "dataType"="string", "required"=false},
	 *     {"name"="id", "dataType"="string", "required"=true}
	 *   }
	 * )
	 *
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
    public function visitGameDetailAction(Request $request) {
    	$gameId = $this->requireId($request->request, 'id');
	    $this->game()->gamePlayerNumberIncrement($gameId);

	    return $this->sucessResponse();
    }
}