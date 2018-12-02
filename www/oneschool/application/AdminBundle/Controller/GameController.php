<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 9/9/16
 * Time: 12:21 PM
 */

namespace Lychee\Bundle\AdminBundle\Controller;


use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Storage\QiniuStorage;
use Lychee\Module\Game\BannerManager;
use Lychee\Module\Game\Entity\Banner;
use Lychee\Module\Game\Entity\Game;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * @Route("/game")
 * Class GameController
 * @package Lychee\Bundle\AdminBundle\Controller
 */
class GameController extends BaseController {

	public function getTitle() {
		return '游戏模块';
	}

	/**
	 * @Route("/")
	 * @Template
	 * @param Request $request
	 *
	 * @return array
	 */
	public function indexAction(Request $request) {
		$em = $this->getDoctrine()->getManager();
		$apps = $em->getRepository(Game::class)->findBy([], ['createTime' => 'DESC']);
		if (null !== $apps) {
			$apps = array_map(function(/** @var Game $app */$app){
				$screenshotsArr = json_decode($app->getAppScreenshots(), true);
				$app->screenshots = $screenshotsArr;

				return $app;
			}, $apps);
		} else {
			$apps = [];
		}

		return $this->response('游戏应用', [
			'apps' => $apps
		]);
	}

	/**
	 * @Route("/detail/{id}", requirements={"id":"\d+"})
	 * @Method("GET")
	 * @Template
	 * @param $id
	 * @return array
	 */
	public function detail($id) {
		/**
		 * @var Game $game
		 */
		$categories = $this->gameCategory()->getCategories();
		$game = $this->game()->fetchOne($id);
		if (!$game) {
			return $this->redirectErrorPage('没有找到指定游戏.', $this->get('Request'));
		}
		$screenshots = json_decode($game->getAppScreenshots());
		return $this->response('游戏详情', [
			'application' => $game,
			'screenshots' => $screenshots,
			'categories' => $categories
		]);
	}

    /**
     * @return array
     * @Route("/create_game")
     * @Template
     */
    public function createAction() {
        $categories = $this->gameCategory()->getCategories();
        return $this->response('添加游戏', [
            'categories' => $categories
        ]);
    }
	/**
	 * @Route("/create")
	 * @param Request $request
	 * @Method("POST")
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function createGameAction(Request $request) {
		$appName = $request->request->get('app_name');
		$description = $request->request->get('description');
		$shortDescription = $request->request->get('short_description');
		$appType = $request->request->get('app_type');
		$iosSize = $request->request->get('ios_size');
		$androidSize = $request->request->get('android_size');
		$iosLink = $request->request->get('ios_link');
		$androidLink = $request->request->get('android_link');
		$title = $request->request->get('title');
		$topicId = $request->request->get('topic_id');
		$publisher = $request->request->get('app_publisher');
		$playerNumbers = $request->request->get('player_numbers');
		$launchDate = $request->request->get('launch_date');
		$topic = $this->topic()->fetchOne($topicId);
		if (null === $topic) {
			$topicId = null;
		}
		$categoryId = $appType;
		$category = $this->gameCategory()->fetchOneById($categoryId);
		$appType = $category->name;
		/** @var QiniuStorage $storageService */
		$storageService = $this->storage();
		$storageService->setPrefix('game/');
		if ($request->files->has('icon')) {
			$iconFile = $request->files->get('icon');
			if (file_exists($iconFile)) {
				$icon = $storageService->put($iconFile);
			}
		}
		$screenshots = [];
		if ($request->files->has('screenshots')) {
			$screenshotFiles = $request->files->get('screenshots');
			foreach ($screenshotFiles as $file) {
				if (file_exists($file)) {
					$screenshots[] = $storageService->put($file);
				}
			}
		}
		if ($request->files->has('banner')) {
			$bannerFile = $request->files->get('banner');
			if (file_exists($bannerFile)) {
				$banner = $storageService->put($bannerFile);
			}
		}
		$app = new Game();
		$app->setAppName($appName)
		    ->setIcon($icon)
		    ->setShortDescription($shortDescription)
		    ->setDescription($description)
		    ->setAppType($appType)
		    ->setIosSize($iosSize)
		    ->setAndroidSize($androidSize)
		    ->setIosLink($iosLink)
		    ->setAndroidLink($androidLink)
		    ->setTitle($title)
		    ->setTopicId($topicId)
		    ->setAppScreenshots(json_encode($screenshots))
		    ->setBanner($banner)
			->setCategoryId($categoryId)
			->setPublisher($publisher)
			->setPlayerNumbers($playerNumbers)
			->setLaunchDate(new \DateTime($launchDate));
		$em = $this->getDoctrine()->getManager();
		$em->persist($app);
		$em->flush();

		return $this->redirect($this->generateUrl('lychee_admin_game_index'));
	}

	/**
	 * @Route("/delete")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function deleteGameAction(Request $request) {
		$gameId = $request->request->get('app_id');
		/**
		 * @var Game $game
		 */
		$game = $this->game()->fetchOne($gameId);
		if ($game) {
			$this->game()->delete($game);
		}

		return $this->redirect($this->generateUrl('lychee_admin_game_index'));
	}

	/**
	 * @Route("/edit")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function editGame(Request $request) {
		$id = $request->request->get('id');
		/**
		 * @var Game $game
		 */
		$game = $this->game()->fetchOne($id);
		if (!$game) {
			return $this->redirectErrorPage('没有找到指定游戏.', $request);
		}
		$appName = $request->request->get('app_name');
		$shortDescription = $request->request->get('short_description');
		$description = $request->request->get('description');
		$appType = $request->request->get('app_type');
		$iosSize = $request->request->get('ios_size');
		$androidSize = $request->request->get('android_size');
		$iosLink = $request->request->get('ios_link');
		$androidLink = $request->request->get('android_link');
		$title = $request->request->get('title');
		$topicId = $request->request->get('topic_id');
		$publisher = $request->request->get('app_publisher');
		$playerNumbers= $request->request->get('player_numbers');
		$launchDate = $request->request->get('launch_date');
		$categoryId = $appType;
		$category = $this->gameCategory()->fetchOneById($categoryId);
		$appType = $category->name;
		$topic = $this->topic()->fetchOne($topicId);
		if (null === $topic) {
			$topicId = null;
		}
		/** @var QiniuStorage $storageService */
		$storageService = $this->storage();
		$storageService->setPrefix('game/');
		if ($request->files->has('icon')) {
			$iconFile = $request->files->get('icon');
			if (file_exists($iconFile)) {
				$icon = $storageService->put($iconFile);
			}
		}
		$screenshots = [];
		if ($request->files->has('screenshots')) {
			$screenshotFiles = $request->files->get('screenshots');
			foreach ($screenshotFiles as $file) {
				if (file_exists($file)) {
					$screenshots[] = $storageService->put($file);
				}
			}
		}
		if ($request->files->has('banner')) {
			$bannerFile = $request->files->get('banner');
			if (file_exists($bannerFile)) {
				$banner = $storageService->put($bannerFile);
			}
		}
		$game->setAppName($appName)
		    ->setDescription($description)
		    ->setShortDescription($shortDescription)
		    ->setAppType($appType)
		    ->setIosSize($iosSize)
		    ->setAndroidSize($androidSize)
		    ->setIosLink($iosLink)
		    ->setAndroidLink($androidLink)
		    ->setTitle($title)
		    ->setTopicId($topicId)
			->setPublisher($publisher)
			->setPlayerNumbers($playerNumbers)
			->setLaunchDate(new \DateTime($launchDate))
			->setCategoryId($categoryId);
		if (isset($icon)) {
			$game->setIcon($icon);
		}
		if (isset($banner)) {
			$game->setBanner($banner);
		}
		if (!empty($screenshots)) {
			$game->setAppScreenshots(json_encode($screenshots));
		}
		$this->getDoctrine()->getManager()->flush();

		return $this->redirect($this->generateUrl('lychee_admin_game_index'));
	}

	/**
	 * @Route("/category")
	 * @Template
     */
	public function categoryAction() {
		$categories = $this->gameCategory()->getCategories();
		return $this->response('游戏分类', array(
			'categories' => $categories
		));
	}
	/**
	 * @param Request $request
	 * @Route("/category/add")
	 * @Method("POST")
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
	public function addCategoryAction(Request $request) {
		$categoryName = $request->request->get('category_name');
		/** @var QiniuStorage $storageService */
		$storageService = $this->storage();
		$storageService->setPrefix('game/category');
		if ($request->files->has('icon')) {
			$iconFile = $request->files->get('icon');
			if (file_exists($iconFile)) {
				$icon = $storageService->put($iconFile);
			}
		}
		$this->gameCategory()->addCategory($categoryName, $icon);
		return $this->redirect($this->generateUrl('lychee_admin_game_category'));
	}

	/**
	 * @Route("/category_detail/{id}", requirements={"id":"\d+"})
	 * @Method("GET")
	 * @Template
	 * @param $id
	 * @return array
	 */
	public function categoryDetailAction($id) {

		$category = $this->gameCategory()->fetchOneById($id);
		if (!$category) {
			return $this->redirectErrorPage('没有找到指定游戏分类.', $this->get('Request'));
		}
		return $this->response('修改游戏分类', [
			'category' => $category
		]);
	}

	/**
	 * @param Request $request
	 * @Route("/category/edit")
     */
	public function editCategoryAction(Request $request) {
		$categoryId = $request->request->get('categroy_id');
		$categoryName = $request->request->get('category_name');
		/** @var QiniuStorage $storageService */
		$storageService = $this->storage();
		$storageService->setPrefix('game/category');
		if ($request->files->has('icon')) {
			$iconFile = $request->files->get('icon');
			if (file_exists($iconFile)) {
				$icon = $storageService->put($iconFile);
			}
		}
		if (!isset($icon)) {
			$icon = null;
		}
		$this->gameCategory()->editCategory($categoryId, $categoryName, $icon);
		return $this->redirect($this->generateUrl('lychee_admin_game_category'));
	}

	/**
	 * @param Request $request
	 * @Route("/category/delete")
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
	public function deleteCategoryAction(Request $request) {
		$id = $request->request->get('category_id');
		$this->gameCategory()->deleteCategory($id);
		return $this->redirect($this->generateUrl('lychee_admin_game_category'));
	}

	/**
	 * @Route("/column")
	 * @Template
	 */
	public function columnAction() {
		$columns = $this->gameColumns()->getColumns();
		return $this->response('游戏栏目', array(
			'columns' => $columns
		));
	}

	/**
	 * @param Request $request
	 * @Route("/column/add")
	 * @Method("POST")
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function addColumnAction(Request $request) {
		$columnTitle = $request->request->get('column_title');
		$this->gameColumns()->addColumn($columnTitle);
		return $this->redirect($this->generateUrl('lychee_admin_game_column'));
	}

	/**
	 * @param Request $request
	 * @Route("/column/delete")
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function deleteColumnAction(Request $request) {
		$id = $request->request->get('column_id');
		$this->gameColumns()->deleteColumn($id);
		return $this->redirect($this->generateUrl('lychee_admin_game_column'));
	}

	/**
	 * @param Request $request
	 * @Route("/column/edit")
	 */
	public function editColumnAction(Request $request) {
		$id = $request->request->get('column_id');
		$title = $request->request->get('column_title');
		$this->gameColumns()->editColumnTitle($id, $title);
		return $this->redirect($this->generateUrl('lychee_admin_game_column'));
	}

	/**
	 * @param Request $request
	 * @Route("/recommendation")
	 * @Template
     */
	public function gameColumnRecommendationAction(Request $request) {
		$column = $request->query->get('column');
		$columns = $this->gameColumns()->getColumns();
		if ($column) {
			$currentColumnId = $column;
		}
		else {
			$currentColumnId = $columns[0]->id;
		}
		$games = $this->gameColumnsRecommendation()->getGamesByColumnId($currentColumnId);
		$positions = array_column($games, 'position', 'gameId');
		$gameIds = array_keys($games);
		$games = $this->game()->fetch($gameIds);
		return $this->response('栏目游戏推荐', array(
			'currentColumn' => $currentColumnId,
			'columns' => $columns,
			'applications' => $games,
			'positions' => $positions
		));
	}

	/**
	 * @Route("/recommendation/add")
	 * @param Request $request
     */
	public function addRecommendationAction(Request $request) {
		$gameId = $request->request->get('game_id');
		$columnId = $request->request->get('column_id');
		/** @var Game $game */
		$game = $this->game()->fetchOne($gameId);
		$column = $this->gameColumns()->fetchOneById($columnId);
		if (!$game) {
			return $this->redirectErrorPage('没有找到指定游戏.', $this->get('Request'));
		}
		if (!$column) {
			return $this->redirectErrorPage('没有找到指定的栏目.', $this->get('Request'));
		}
		$state = $this->gameColumnsRecommendation()->addRecommendation($game->getId() ,$column->id);
		if (!$state) {
			return $this->redirectErrorPage('该推荐游戏已经存在该栏目，无需重复添加', $this->get('Request'));
		}
		return $this->redirect($this->generateUrl('lychee_admin_game_gamecolumnrecommendation', array('column' => $columnId)));
	}

	/**
	 * @param Request $request
	 * @Route("recommendation/delete")
     */
	public function deleteRecommendationAction(Request $request) {
		$columnId = $request->request->get('column_id');
		$gameId = $request->request->get('application_id');
		$this->gameColumnsRecommendation()->deleteColumnRecommendationGame($columnId,$gameId);
		return $this->redirect($this->generateUrl('lychee_admin_game_gamecolumnrecommendation', array('column' => $columnId)));
	}

	/**
	 * @param Request $request
	 * @Route("recommendation/positionchange")
     */
	public function positionChangeAction(Request $request) {
		$currentPosition = $request->request->get('current_position');
		$nextPosition = $request->request->get('next_position');
		$columnId = $request->request->get('column_id');
		$this->gameColumnsRecommendation()->updateRecommendationPositions($columnId, $currentPosition,$nextPosition);
		return $this->redirect($this->generateUrl('lychee_admin_game_gamecolumnrecommendation', array('column' => $columnId)));
	}
	
	/**
	 * @param Request $request
	 * @Route("/reviewstate")
	 * @Template
	 */
	public function reviewStateAction(Request $request) {
		$appId = $request->query->get('app');
		$currentPage = $request->query->get('page', '1');
		$count = 10;
		$total = 0;
		$reviewstates = [];
		$currentApp = 0;
		$appIds = $this->gameReviewState()->getAppIds();
		if ($appId) {
			$currentApp = $appId;
		} else {
			if (count($appIds)) {
				$currentApp = $appIds[0];
			}
		}
		if (!empty($currentApp)) {
			$reviewstates = $this->gameReviewState()->getReviewStates($currentApp, $currentPage, $count, $total);
		}
		return $this->response('游戏模块开关', array(
			'reviewstates' => $reviewstates,
			'currentApp' => $currentApp,
			'appIds' => $appIds,
			'page' => $currentPage,
			'pageCount' => ceil($total/$count)
		));
	}

	/**
	 * @param Request $request
	 * @Route("/edit_version")
	 */
	public function editVersionAction(Request $request) {
		$appId = $request->request->get('app_id');
		$reviewStateId = $request->request->get('reviewstate_id');
		$version = $request->request->get('new_version');
		$page = $request->request->get(('page'));
		$this->gameReviewState()->editVersion($version, $reviewStateId, $appId);
		return $this->redirect($this->generateUrl('lychee_admin_game_reviewstate', array('app' => $appId, 'page' => $page)));
	}

	/**
	 * @param Request $request
	 * @Route("/add_reviewstate")
	 */
	public function addReviewState(Request $request) {
		$channel = $request->request->get('channel');
		$version = $request->request->get('version');
		$appId = $request->request->get('app_id');
		$state = 0;
		$this->gameReviewState()->addReviewState($channel, $version, $state, $appId);
		return $this->redirect($this->generateUrl('lychee_admin_game_reviewstate', array('app' => $appId)));
	}

	/**
	 * @param Request $request
	 * @Route("/edit_state")
	 */
	public function editReviewState(Request $request) {
		$ids = $request->request->get('reviewstate_ids');
		$appId = $request->request->get('app_id');
		$state = $request->request->get('state');
		$page = $request->request->get('page');
		$ids = preg_split("/[,]+/", $ids);
		$this->gameReviewState()->editReviewState($ids, $appId, $state);
		return $this->redirect($this->generateUrl('lychee_admin_game_reviewstate',array('app' => $appId, 'page' => $page)));
	}
}