<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 8/23/16
 * Time: 6:32 PM
 */

namespace Lychee\Bundle\AdminBundle\Controller;

use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Storage\QiniuStorage;
use Lychee\Component\Storage\StorageException;
use Lychee\Module\ContentManagement\Entity\AppLaunchImage;
use Lychee\Module\Game\BannerManager;
use Lychee\Module\Promotion\PromotionService;
use Lychee\Module\Promotion\Entity\Campaign;
use Lychee\Module\Promotion\Entity\CampaignTopic;
use Lychee\Module\Recommendation\Entity\Banner;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AdController
 * @package Lychee\Bundle\AdminBundle\Controller
 * @Route("/ad")
 */
class AdController extends BaseController {

    public function getTitle() {
        return '广告管理';
    }

    /**
     * @Route("/")
     * @Template
     * @param Request $request
     * @return array
     */
    public function indexAction(Request $request) {
        $page = $request->query->get('page', 1);
        /** @var PromotionService $adService */
        $adService = $this->get('lychee.module.promotion');
        $count = 10;
        $campaigns = $adService->fetchCampaignsByPage($page, $count);
        $total = $adService->getTotalCount();
        $pages = ceil($total / $count);

        $campaignTopics = array_reduce($campaigns, function($result, $item) use ($adService) {
            /** @var Campaign $item */
            $campaignTopics = $adService->getTopicIdsByCampaign($item->id);
            $result[$item->id] = array_map(function($elem) {
                /** @var CampaignTopic $elem */
                return [$elem->topicId, $elem->position];
            }, $campaignTopics);

            return $result;
        });
        $campaignViews = array_reduce($campaigns, function($result, $item) use ($adService) {
            /** @var Campaign $item */
            $result[$item->id] = $adService->getViews($item->id);

            return $result;
        });
        $topicIds = [];
        if ($campaignTopics) {
            foreach ($campaignTopics as $ct) {
                foreach ($ct as $t) {
                    $topicIds[] = $t[0];
                }
            }
            $topicIds = array_unique($topicIds);
        }
        $topics = $this->topic()->fetch($topicIds);

        return $this->response('次元内广告', [
            'campaigns' => $campaigns,
            'campaignTopics' => $campaignTopics,
            'views' => $campaignViews,
            'topics' => $topics,
            'page' => $page,
            'pages' => $pages,
            'now' => new \DateTime(),
        ]);
    }

    /**
     * @Route("/create")
     * @Template
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createAdAction(Request $request) {
        $campaignId = $request->query->get('id');
        $campaign = null;
        $campaignTopics = null;
        if ($campaignId) {
            /** @var PromotionService $adService */
            $adService = $this->get('lychee.module.promotion');
            /** @var Campaign $campaign */
            $campaign = $adService->fetchCampaign($campaignId);
            $now = new \DateTime();
            if ($campaign->endTime < $now) {
                return $this->redirectErrorPage('禁止编辑已结束的广告活动', $request);
            }
            $campaignTopics = $adService->getTopicIdsByCampaign($campaignId);
        }

        return $this->response('创建次元内广告', [
            'campaign' => $campaign,
            'campaignTopics' => $campaignTopics,
        ]);
    }

    /**
     * @Route("/create/do")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function doCreateAdAction(Request $request) {
        $campaignId = $request->request->get('id');
        $link = $request->request->get('link');
        $startTime = $request->request->get('start_time', date('Y-m-d'));
        $endTime = $request->request->get('end_time', date('Y-m-d'));
        $startTime = new \DateTime($startTime);
        $endTime = new \DateTime($endTime);
        $topics = $request->request->get('topics');
        $positions = $request->request->get('position');
        $topicsPosition = [];
        for ($i = 0, $count = count($topics); $i < $count; $i++) {
            if ($topics[$i]) {
                $topicsPosition[] = [$topics[$i], $positions[$i]];
            }
        }
        $image = $request->files->get('image');
        if ($image) {
            try {
                $imageUrl = $this->storage()->put($image);
            } catch (StorageException $e) {
                return $this->redirectErrorPage('上传图片失败: ' . $e->getMessage(), $request);
            }
        } else {
            if (!$campaignId) {
                return $this->redirectErrorPage('请上传图片', $request);
            }
        }
        /** @var PromotionService $adService */
        $adService = $this->get('lychee.module.promotion');

        if (!$campaignId) {
            if ($image) {
                try {
                    $imageUrl = $this->storage()->put($image);
                } catch (StorageException $e) {
                    return $this->redirectErrorPage('上传图片失败: ' . $e->getMessage(), $request);
                }
            } else {
                return $this->redirectErrorPage('请上传图片', $request);
            }

            $isOccupancy = $adService->isOccupancy($topicsPosition, $startTime, $endTime);
            if (!empty($isOccupancy)) {
                $topicIds = array_map(function($item) {
                    return $item[0];
                }, $isOccupancy);
                return $this->redirectErrorPage(
                    sprintf("以下次元的广告位置在指定时间段内已被使用: %s", implode(',', $topicIds)),
                    $request
                );
            }
            $adService->createCampaign($link, $imageUrl, $startTime, $endTime, $topicsPosition);
        } else {
            $now = new \DateTime();
            /** @var Campaign $campaign */
            $campaign = $adService->fetchCampaign($campaignId);
            if ($campaign) {
                if ($campaign->endTime < $now) {
                    return $this->redirectErrorPage('禁止编辑已结束的推广活动', $request);
                }
                $campaign->link = $link;
                $campaign->startTime = $startTime;
                $campaign->endTime = $endTime;
                if (isset($imageUrl)) {
                    $campaign->image = $imageUrl;
                }
                $adService->updateCampaign($campaign, $topicsPosition);
            }
        }

        return $this->redirect($this->generateUrl('lychee_admin_ad_index'));
    }

    /**
     * @Route("/remove")
     * @Method("POST")
     * @param Request $request
     * @return JsonResponse
     */
    public function removeCampaignAction(Request $request) {
        $campaignId = $request->request->get('id');
        /** @var PromotionService $adService */
        $adService = $this->get('lychee.module.promotion');
        $adService->removeCampaign($campaignId);

        return new JsonResponse();
    }

	/**
	 * @Route("/banner")
	 * @Template
	 * @return array
	 */
	public function bannerAction()
	{
		$banners = $this->recommendationBanner()->fetchAllBanners();
		$availableBanners = [];
		$unusedBanners = [];
		foreach ($banners as $banner) {
			if (null !== $banner->position) {
				$availableBanners[] = $banner;
			} else {
				$unusedBanners[] = $banner;
			}
		}
		usort($availableBanners, function($a, $b) {
			if ($a->position > $b->position) {
				return 1;
			} else {
				return 0;
			}
		});

		return $this->response('首页广告', array(
			'availableBanners' => $availableBanners,
			'unusedBanners' => $unusedBanners,
		));
	}

	/**
	 * @Route("/banners/{page}", requirements={"page" = "\d+"})
	 * @Template
	 * @param int $page
	 *
	 * @return array
	 */
	public function bannersAction($page = 1) {
		$count = 20;
		$banners = $this->recommendationBanner()->fetchBannersByPage($page, $count);
		$bannerCount = $this->recommendationBanner()->getBannersCount();
		$pages = ceil($bannerCount / $count);

		return $this->response('所有首页广告', array(
			'banners' => $banners,
			'page' => $page,
			'pages' => $pages,
		));
	}

	/**
	 * @Route("/create_banner")
	 * @Template
	 * @return array
	 */
	public function createBannerAction()
	{
		return $this->response('创建首页广告');
	}

	/**
	 * @Route("/banner/create")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
	 * @throws \Exception
	 */
	public function doCreateBannerAction(Request $request)
	{
		do {
			if (true === $request->files->has('image')) {
				$imageFile = $request->files->get('image');
				if ($imageFile) {
					$imageSize = getimagesize($imageFile);
					$imageUrl = $this->storage()->put($imageFile);
					break;
				}
			}
			throw new \Exception('Image File is Not Uploaded');
		} while(0);

		$shareImageUrl = null;
		if (true === $request->files->has('share_image')) {
			$shareImageFile = $request->files->get('share_image');
			if ($shareImageFile) {
				$shareImageUrl = $this->storage()->put($shareImageFile);
			}
		}
		$shareBigImageUrl = null;
		if (true === $request->files->has('share_big_image')) {
			$shareBigImageFile = $request->files->get('share_big_image');
			if ($shareBigImageFile) {
				$shareBigImageUrl = $this->storage()->put($shareBigImageFile);
			}
		}
		$promotion = new Banner();
		$promotion->title = $request->request->get('title');
		$promotion->description = $request->request->get('description');
		$promotion->url = trim($request->request->get('url'));
		$promotion->imageUrl = $imageUrl;
		$promotion->imageWidth = $imageSize[0];
		$promotion->imageHeight = $imageSize[1];
		$promotion->shareTitle = $request->request->get('share_title');
		$promotion->shareText = $request->request->get('share_text');
		$promotion->shareImageUrl = $shareImageUrl;
		$promotion->shareBigImageUrl = $shareBigImageUrl;

		$validator = $this->get('validator');
		$errors = $validator->validate($promotion);
		if (count($errors) > 0) {
			$errorsString = (string) $errors;

			return new Response($errorsString);
		}

		$em = $this->getDoctrine()->getManager();
		$em->persist($promotion);
		$em->flush();
		$this->clearCache();

		return $this->redirect($this->generateUrl('lychee_admin_ad_banner'));
	}

	private function clearCache() {
		$this->container->get('memcache.default')->delete('rec_web');
		$this->container->get('memcache.default')->delete('rec_web2');
	}

	/**
	 * @Route("/banners_available")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function bannersAvailableAction(Request $request)
	{
		$promotionIds = $request->request->get('promotion_ids');
		$uniquePromotionIds = [];
		foreach ($promotionIds as $pid) {
			if (is_numeric($pid)) {
				$uniquePromotionIds[] = $pid;
			}
		}
		if (!empty($uniquePromotionIds)) {
			$maxPosition = $this->recommendationBanner()->getMaxPosition();
			$banners = $this->recommendationBanner()->fetchAllBanners();
			$banners = ArrayUtility::mapByColumn($banners, 'id');
			foreach ($uniquePromotionIds as $pid) {
				$maxPosition += 1;
				$current = $banners[$pid];
				if (null === $current->position) {
					$current->position = $maxPosition;
					$this->recommendationBanner()->updateBanner($current);
				}
			}
			$this->clearCache();
		}

		return $this->redirect($this->generateUrl('lychee_admin_ad_banner'));
	}

	/**
	 * @Route("/remove_banner")
	 * @Method("POST")
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function removeBannerAction(Request $request)
	{
		$availableIds = $request->request->get('available_ids');
		$this->recommendationBanner()->updateAvailableBanners($availableIds);
		$this->clearCache();

		return new JsonResponse();
	}

	/**
	 * @deprecated
	 * @Route("/sort_promotions")
	 * @Method("POST")
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function sortPromotionsAction(Request $request)
	{
		$sortedIds = $request->request->get('sorted_ids');
		$this->recommendationBanner()->updateAvailableBanners($sortedIds);
		$this->clearCache();

		return new JsonResponse();
	}

	/**
	 * @Route("/delete_banner")
	 * @Method("POST")
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function deleteBannerAction(Request $request)
	{
		$bid = $request->request->get('bid');
		$banner = $this->recommendationBanner()->fetchOneBanner($bid);
		if (null === $banner) {
			throw $this->createNotFoundException('Banner Not Found');
		}
		$em = $this->getDoctrine()->getManager();
		$em->remove($banner);
		$em->flush();
		$this->clearCache();

		return new JsonResponse();
	}

	/**
	 * @Route("/game/banner/published")
	 * @Template
	 * @return array
	 */
	public function publishedGameBanner() {
		/**
		 * @var BannerManager $bannerService
		 */
		$bannerService = $this->get('lychee.module.game_banner');
		$banners = $bannerService->fetchAllBanners();
		$publishedBanners = [];
		$unpublishedBanners = [];
		foreach ($banners as $banner) {
			/** @var Banner $banner */
			if (null !== $banner->position) {
				$publishedBanners[] = $banner;
			} else {
				$unpublishedBanners[] = $banner;
			}
		}

		return $this->response('已发布的游戏广告', [
			'banners' => $publishedBanners,
			'unusedBanners' => array_reverse($unpublishedBanners),
		]);
	}

	/**
	 * @Route("/game/banner/create")
	 * @Template
	 * @return array
	 */
	public function createGameBanner() {
		return $this->response('创建游戏广告');
	}

	/**
	 * @Route("/game/banner")
	 * @Template
	 * @param int $page
	 *
	 * @return array
	 */
	public function gameBanner($page = 1) {
		/**
		 * @var BannerManager $bannerService
		 */
		$bannerService = $this->get('lychee.module.game_banner');
		$count = 20;
		$banners = $bannerService->fetchBannersByPage($page, $count);
		$bannerCount = $bannerService->getBannersCount();

		$pages = ceil($bannerCount / $count);

		return $this->response('游戏广告', [
			'banners' => $banners,
			'bannerCount' => $bannerCount,
			'page' => $page,
			'pages' => $pages,
		]);
	}

	/**
	 * @Route("/game/banner/publish")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function publishGameBanner(Request $request) {
		/** @var BannerManager $bannerService */
		$bannerService = $this->get('lychee.module.game_banner');

		$promotionIds = $request->request->get('promotion_ids');
		$uniquePromotionIds = [];
		foreach ($promotionIds as $pid) {
			if (is_numeric($pid)) {
				$uniquePromotionIds[] = $pid;
			}
		}
		if (!empty($uniquePromotionIds)) {
			$maxPosition = $bannerService->getMaxPosition();
			$banners = $bannerService->fetchAllBanners();
			$banners = ArrayUtility::mapByColumn($banners, 'id');
			foreach ($uniquePromotionIds as $pid) {
				$maxPosition += 1;
				$current = $banners[$pid];
				if (null === $current->position) {
					$current->position = $maxPosition;
					$bannerService->updateBanner($current);
				}
			}
		}

		return $this->redirect($this->generateUrl('lychee_admin_ad_publishedgamebanner'));
	}

	/**
	 * @Route("/game/banner/sort")
	 * @Method("POST")
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function sortGameBannersAction(Request $request) {
		$sortedIds = $request->request->get('sorted_ids');
		/** @var BannerManager $bannerService */
		$bannerService = $this->get('lychee.module.game_banner');
		$bannerService->updateAvailableBanners($sortedIds);

		return new JsonResponse();
	}

	/**
	 * @Route("/game/banner/remove")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function removeGameBanner(Request $request) {
		/** @var BannerManager $bannerService */
		$bannerService = $this->get('lychee.module.game_banner');
		$availableIds = $request->request->get('available_ids');
		$bannerService->updateAvailableBanners($availableIds);

		return new JsonResponse();
	}

	/**
	 * @Route("/game/banner/docreate")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 * @throws \Exception
	 */
	public function doCreateGameBannerAction(Request $request) {
		/** @var QiniuStorage $storageService */
		$storageService = $this->storage();
		$storageService->setPrefix('game/');
		do {
			if (true === $request->files->has('image')) {
				$imageFile = $request->files->get('image');
				if ($imageFile) {
					$imageSize = getimagesize($imageFile);
					$imageUrl = $storageService->put($imageFile);
					break;
				}
			}
			throw new \Exception('Image File is Not Uploaded');
		} while(0);

		$shareImageUrl = null;
		if (true === $request->files->has('share_image')) {
			$shareImageFile = $request->files->get('share_image');
			if ($shareImageFile) {
				$shareImageUrl = $storageService->put($shareImageFile);
			}
		}
		$shareBigImageUrl = null;
		if (true === $request->files->has('share_big_image')) {
			$shareBigImageFile = $request->files->get('share_big_image');
			if ($shareBigImageFile) {
				$shareBigImageUrl = $storageService->put($shareBigImageFile);
			}
		}
		$promotion = new \Lychee\Module\Game\Entity\Banner();
		$promotion->title = $request->request->get('title');
		$promotion->description = $request->request->get('description');
		$promotion->url = $request->request->get('url');
		$promotion->imageUrl = $imageUrl;
		$promotion->imageWidth = $imageSize[0];
		$promotion->imageHeight = $imageSize[1];
		$promotion->shareTitle = $request->request->get('share_title');
		$promotion->shareText = $request->request->get('share_text');
		$promotion->shareImageUrl = $shareImageUrl;
		$promotion->shareBigImageUrl = $shareBigImageUrl;

		$validator = $this->get('validator');
		$errors = $validator->validate($promotion);
		if (count($errors) > 0) {
			$errorsString = (string) $errors;

			return $this->redirectErrorPage($errorsString, $request);
		}

		$em = $this->getDoctrine()->getManager();
		$em->persist($promotion);
		$em->flush();

		return $this->redirect($this->generateUrl('lychee_admin_ad_gamebanner'));
	}

	/**
	 * @Route("/game/banner/delete")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function deleteGameBanner(Request $request) {
		$id = $request->request->get('bid');
		/** @var BannerManager $bannerService */
		$bannerService = $this->get('lychee.module.game_banner');
		$bannerService->deleteBanner($id);

		return new JsonResponse();
	}

	/**
	 * @Route("/launcher")
	 * @Template
	 * @return array
	 */
	public function launcherAction()
	{
		$appLaunchImages = $this->contentManagement()->fetchAppLaunchImages();
		$minAppLaunchImage = null;
		$allImagesSize = [];
		foreach ($appLaunchImages as $appLaunchImage) {
			$allImagesSize[] = array(
				'width' => $appLaunchImage->getWidth(),
				'height' => $appLaunchImage->getHeight(),
			);
			if ($minAppLaunchImage instanceof AppLaunchImage) {
				if ($appLaunchImage->getWidth() < $minAppLaunchImage->getWidth()) {
					$minAppLaunchImage = $appLaunchImage;
				}
			} else {
				$minAppLaunchImage = $appLaunchImage;
			}
		}

		return $this->response('启动页', array(
			'minAppLaunchImage' => $minAppLaunchImage,
			'allImagesSize' => $allImagesSize,
		));
	}

	/**
	 * @Route("/launch_image")
	 * @Template
	 * @return array
	 */
	public function launchImageAction()
	{
		return $this->response('上传启动页');
	}

	/**
	 * @Route("/launcher/images/upload")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function imagesUploadAction(Request $request)
	{
		if (true === $request->files->has('launch_images')) {
			$imageFiles = $request->files->get('launch_images');
			$appLaunchImages = [];
			$newSizes = [];
			foreach ($imageFiles as $imageFile) {
				if ($imageFile) {
					list($width, $height) = getimagesize($imageFile);
					$newSizes[] = $width . ' ' . $height;
					$url = $this->storage()->put($imageFile);
					$appLaunchImages[] = new AppLaunchImage($url, $width, $height);
				}
			}
			if (false === empty($appLaunchImages)) {
				$oldImages = $this->contentManagement()->fetchAppLaunchImages();
				foreach ($oldImages as $appLaunchImage) {
					/** @var AppLaunchImage $appLaunchImage */
					$sizeKey = $appLaunchImage->getWidth() . ' ' . $appLaunchImage->getHeight();
					if (!in_array($sizeKey, $newSizes)) {
						$appLaunchImages[] = new AppLaunchImage(
							$appLaunchImage->getUrl(),
							$appLaunchImage->getWidth(),
							$appLaunchImage->getHeight()
						);
					}
				}
				$this->contentManagement()->updateAppLaunchImages($appLaunchImages);
			}
		}

		return $this->redirect($this->generateUrl('lychee_admin_ad_launcher'));
	}
}