<?php

namespace Lychee\Bundle\AdminBundle\Controller;

use Lychee\Bundle\AdminBundle\Components\Foundation\Paginator;
use Lychee\Bundle\AdminBundle\Entity\CustomizeContent;
use Lychee\Bundle\AdminBundle\Service\DuplicateCustomizeContentException;
use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Foundation\ImageUtility;
use Lychee\Module\Recommendation\Entity\RecommendationItem;
use Lychee\Module\Recommendation\RecommendationType;
use Lychee\Module\Search\TopicSearcher;
use Lychee\Module\Topic\Entity\TopicCreatingApplication;
use Lychee\Module\Topic\Exception\RunOutOfCreatingQuotaException;
use Lychee\Module\Topic\Exception\TopicAlreadyExistException;
use Lychee\Module\Topic\TopicCategoryService;
use Lychee\Module\Topic\TopicParameter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class TopicController
 * @package Lychee\Bundle\AdminBundle\Controller
 * @Route("/topic")
 */
class TopicController extends BaseController
{

    const TOPIC_COUNT_PER_PAGE = 50;

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->container->getParameter('topic_name') . '管理';
    }

    /**
     * @Route("/search_focus")
     * @param Request $request
     * @return JsonResponse
     */
    public function searchFocusAction(Request $request)
    {
        $keyword = $request->query->get('keyword');
        if (empty($keyword)) {
            return new JsonResponse([]);
        }
        $topics = array();
        $topic = $this->topic()->fetchOne($keyword);
        if (empty($topic)) {
            return new JsonResponse($topics);
        }
        $topics[$topic->id] = $topic;

        return new JsonResponse($topics);
    }

    /**
     * @Route("/search")
     * @param Request $request
     * @return JsonResponse
     */
    public function searchAction(Request $request)
    {
        $keyword = $request->query->get('keyword');
        $topics = array();
        if ($keyword) {
            $topics = $this->topic()->fetchByKeyword($keyword, 0, 20);
        }

        return new JsonResponse($topics);
    }

    /**
     * @Route("/")
     * @Template
     * @param Request $request
     * @return mixed
     */
    public function indexAction(Request $request)
    {
        $query = $request->query->get('query');
        $paginator = null;
        $nextCursor = null;
        if (!$query) {
            $cursor = $request->query->get('cursor', PHP_INT_MAX);
            if (0 >= $cursor) {
                $cursor = PHP_INT_MAX;
            }
            $iterator = $this->topic()->iterateTopic('DESC');
            $paginator = new Paginator($iterator);
            $paginator->setCursor($cursor)
                ->setPage($request->query->get('page', 1))
                ->setStep(30)
                ->setStartPageNum($request->query->get('start_page', 1));
            $topics = $paginator->getResult();
        } else {
            $resultById = [];
            if (is_numeric($query)) {
                $resultById[] = $this->topic()->fetchOne($query);
            }
            $resultByKw = $this->topic()->fetchByKeyword($query, 0, self::TOPIC_COUNT_PER_PAGE, $nextCursor);
            if (is_array($resultById)) {
                $topics = array_merge($resultById, $resultByKw);
            } else {
                $topics = $resultByKw;
            }
        }
        /**
         * @var \Lychee\Module\Topic\TopicCategoryService $topicCategoryService
         */
        $topicCategoryService = $this->container->get('lychee.module.topic.category');
        $properties = $topicCategoryService->getProperties();
        $categories = $topicCategoryService->getCategories();

        return $this->response($this->container->getParameter('topic_name') . '管理', array(
            'paginator' => $paginator,
            'topics' => $topics,
            'query' => $query,
            'properties' => $properties,
            'categories' => $categories,
            'nextCursor' => $nextCursor,
        ));
    }

    /**
     * @Route("/post/{id}", requirements={"id" = "\d+"})
     * @Method("GET")
     * @Template
     * @param $id
     * @return array
     */
    public function postAction($id) {
        $topic = $this->topic()->fetchOne($id);

        return $this->response('次元: ' . $topic->title, [
            'topicId' => $id,
            'tags' => $this->get('lychee_admin.service.tag')->fetchAllTags(),
        ]);
    }

    /**
     * @Route("/load_posts/{topicId}/{cursor}", requirements={"topicId" = "\d+"})
     * @Template
     * @param $topicId
     * @param $cursor
     * @return JsonResponse
     */
    public function loadPostsAction($topicId, $cursor = 0) {
        $postIds = $this->post()->fetchIdsByTopicId($topicId, (int)$cursor, 10, $nextCursor);
        rsort($postIds);
        if (0 == $cursor) {
            $stickyPostIds = $this->postSticky()->getStickyPostIds($topicId, 0, 100, $stickyNextCursor);
            $postIds = array_merge($stickyPostIds, $postIds);
        }
        $posts = $this->post()->fetch($postIds);
        if (0 != $cursor) {
            krsort($posts);
        }
        $result = $this->getCardData($posts);

        return new JsonResponse(array('total' => count($result), 'result' => $result));
    }

    /**
     * @Route("/create")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createAction(Request $request) {
        $topicId = $request->request->get('topic_id');
        $topicName = $request->request->get('topic_name');
        $creatorId = $request->request->get('creator');
//        $opMark = $request->request->get('op_mark');
        $topicDesc = str_replace("\r", '', $request->request->get('description'));
        $property = $request->request->get('property');
        $categories = $request->request->get('categories');
        $managerId = $request->request->get('manager_id');
        $creator = $this->account()->fetchOne($creatorId);
        if (null === $creator) {
            return $this->redirect($this->generateUrl('lychee_admin_topic_error', [
                'errorMsg' => '账号不存在',
                'callbackUrl' => $this->generateUrl('lychee_admin_topic_index',[], UrlGeneratorInterface::ABSOLUTE_URL),
            ]));
        }
        $indexImageUrl = null;
        if ($request->files->has('index_image')) {
            $imageFile = $request->files->get('index_image');
            if (file_exists($imageFile)) {
                $indexImageUrl = $this->storage()->put($imageFile);
            }
        }
        if (!$topicId) {
            $this->topic()->increaseUserCreatingQuota($creatorId, 1);
            try {
                $p = new TopicParameter();
                $p->creatorId = $creatorId;
                $p->title = $topicName;
                $p->description = $topicDesc;
                $p->indexImageUrl = $indexImageUrl;
                $topic = $this->topic()->create($p);
            } catch (TopicAlreadyExistException $e) {
                return $this->redirect($this->generateUrl('lychee_admin_topic_error', [
                    'errorMsg' => $this->getParameter('topic_name') . '已存在',
                    'callbackUrl' => $request->headers->get('referer'),
                ]));
            } catch (RunOutOfCreatingQuotaException $e) {
                return $this->redirect($this->generateUrl('lychee_admin_topic_error', [
                    'errorMsg' => sprintf("创建%s配额不足", $this->getParameter('topic_name')),
                    'callbackUrl' => $request->headers->get('referer'),
                ]));
            } catch (\Exception $e) {
                return $this->redirect($this->generateUrl('lychee_admin_topic_error', [
                    'errorMsg' => $e->getMessage(),
                    'callbackUrl' => $request->headers->get('referer'),
                ]));
            }
            $topicId = $topic->id;
        } else {
            $topic = $this->topic()->fetchOne($topicId);
            if (null !== $topic) {
                $topic->title = $topicName;
                $topic->creatorId = $creatorId;
                $topic->description = $topicDesc;
                if (null !== $indexImageUrl) {
                    $topic->indexImageUrl = $indexImageUrl;
                }
                $this->topic()->update($topic);
            }
        }
        if ($topic->managerId != $managerId) {
            $this->topic()->updateManager($topicId, $managerId);
        }
        $topicCategories = [];
        if ($property) {
            $topicCategories[] = $property;
        }
        if (!empty($categories)) {
            $topicCategories = array_merge($topicCategories, $categories);
        }
        /**
         * @var \Lychee\Module\Topic\TopicCategoryService $topicCategoryService
         */
        $topicCategoryService = $this->container->get('lychee.module.topic.category');
        $topicCategoryService->topicAddCategories($topicId, $topicCategories);

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/fetchone")
     * @param Request $request
     * @return JsonResponse
     */
    public function fetchOneAction(Request $request) {
        $id = $request->query->get('id');
        $topic = $this->topic()->fetchOne($id);
        if (null === $topic) {
            throw $this->createNotFoundException('Topic Not Found.');
        }
        $topic->indexImageUrl = ImageUtility::resize($topic->indexImageUrl, 320, 9999);
        /**
         * @var \Lychee\Module\Topic\TopicCategoryService $topicCategoryService
         */
        $topicCategoryService = $this->container->get('lychee.module.topic.category');
        $property = $topicCategoryService->getPropertyByTopicId($id);
        $categories = $topicCategoryService->getCategoriesByTopicId($id);
        $categories = ArrayUtility::columns($categories, 'id');

        return new JsonResponse([
            'topic' => $topic,
            'property' => $property,
            'categories' => $categories,
        ]);
    }

    /**
     * @Route("/detail/{topicId}", requirements={"topicId" = "\d+"})
     * @Template
     * @param $topicId
     * @return array
     */
    public function detailAction($topicId) {
        $topic = $this->topic()->fetchOne($topicId);
        if (!$topic) {
            throw $this->createNotFoundException('Topic Not Found');
        }
        /**
         * @var \Lychee\Module\Topic\TopicCategoryService $topicCategoryService
         */
        $topicCategoryService = $this->get('lychee.module.topic.category');
        $topicProperty = $topicCategoryService->getPropertyByTopicId($topicId);
        $topicCategories = array_map(function($category) {
            return $category['name'];
        }, $topicCategoryService->getCategoriesByTopicId($topicId));
        $creator = null;
        if ($topic->creatorId) {
            $creator = $this->account()->fetchOne($topic->creatorId);
        }
        $manager = null;
        if ($topic->creatorId === $topic->managerId) {
            $manager = $creator;
        } elseif ($topic->managerId) {
            $manager = $this->account()->fetchOne($topic->managerId);
        }
        $topicFollowerCount = $this->topicFollowing()->getTopicsFollowerCounter(array($topicId))
            ->getCount($topicId);
        $topic->indexImageUrl = ImageUtility::resize($topic->indexImageUrl, 240, 9999);
        $topic->coverImageUrl = ImageUtility::resize($topic->coverImageUrl, 240, 9999);
        /**
         * @var Request $request
         */
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        if ($startDate && $endDate) {
            $startDate = new \DateTime($startDate);
            $endDate = new \DateTime($endDate);
            $postCount = $this->topic()->countPost($topicId, $startDate, $endDate);
            $commentCount = $this->topic()->countComment($topicId, $startDate, $endDate);
            $followerCount = $this->topic()->countFollower($topicId, $startDate, $endDate);
        } else {
            $postCount = $commentCount = $followerCount = null;
        }
        /**
         * @var \Lychee\Module\Topic\TopicCategoryService $topicCategoryService
         */
        $topicCategoryService = $this->container->get('lychee.module.topic.category');
        $properties = $topicCategoryService->getProperties();
        $categories = $topicCategoryService->getCategories();

        return $this->response('次元详情', [
            'topic' => $topic,
            'creator' => $creator,
            'manager' => $manager,
            'topicFollowerCount' => $topicFollowerCount,
            'postCount' => $postCount,
            'commentCount' => $commentCount,
            'followerCount' => $followerCount,
            'topicProperty' => $topicProperty,
            'topicCategories' => $topicCategories,
            'properties' => $properties,
            'categories' => $categories,
        ]);
    }

    /**
     * @Route("/remove")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeAction(Request $request) {
        $id = $request->request->get('topic_id');
        $this->get('lychee.module.topic.deletor')->delete($id);
        $referer = $request->headers->get('referer');

        return $this->redirect($referer);
    }

    /**
     * @Route("/hide_topics/{page}")
     * @Template
     * @param int $page
     * @return array
     */
    public function hideTopicListAction($page = 1) {
        $limit = 30;
        $topics = $this->topic()->listHideTopic($page, $limit);
        $count = $this->topic()->hideTopicsCount();

        return $this->response('隐蔽次元', [
            'topics' => $topics,
            'page' => $page,
            'count' => $count,
            'pages' => ceil($count / $limit)
        ]);
    }

    /**
     * @Route("/hide_or_unhide")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function hideOrUnhideTopicAction(Request $request) {
        $topicId = $request->request->get('topic_id');
        $topic = $this->topic()->fetchOne($topicId);
        if ($topic) {
            if ($topic->hidden == 0) {
                $this->topic()->hide($topicId);
            } else {
                $this->topic()->unhide($topicId);
            }
        }

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/add_hide_topics")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addHideTopicsAction(Request $request) {
        $topicIds = $request->request->get('topic_ids');
        $topicIds = explode(',', $topicIds);
        if (is_array($topicIds)) {
            foreach ($topicIds as $tid) {
                if (is_numeric($tid)) {
                    $this->topic()->hide($tid);
                }
            }
        }
        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/load_more")
     * @param Request $request
     * @return JsonResponse
     */
    public function loadMoreTopicsAction(Request $request) {
        $keyword = $request->query->get('keyword');
        $cursor = $request->query->get('cursor');
        $topics = $this->topic()->fetchByKeyword($keyword, $cursor, self::TOPIC_COUNT_PER_PAGE, $nextCursor);

        return new JsonResponse([
            'topics' => array_reduce($topics, function($result, $t) {
                $result[] = [
                    'id' => $t->id,
                    'title' => $t->title,
                    'createTime' => $t->createTime->format('Y-m-d H:i:s'),
                    'postCount' => $t->postCount,
                    'opMark' => $t->opMark? $t->opMark:'',
                    'deleted' => $t->deleted,
                    'hidden' => $t->hidden,
                ];
                return $result;
            }),
            'cursor' => $nextCursor,
        ]);
    }

    /**
     * @Route("/category/{catId}", requirements={"catId" = "\d+"})
     * @Template
     *
     * @param $catId
     * @param Request $request
     * @return array
     */
    public function categoryAction($catId, Request $request) {
    	$search = $request->query->get('search');
        /** @var TopicCategoryService $catService */
        $catService = $this->get('lychee.module.topic.category');
        $cats = $catService->getCurrentCategories();
        if (!$catId && $cats) {
            $catId = $cats[0]->id;
        }
        if ($search) {
        	/** @var TopicSearcher $topicSearcher */
	        $topicSearcher = $this->get('lychee.module.search.topicSearcher');
	        $allTopicIds = $topicSearcher->search($search, 0, 100);
        } else {
	        $allTopicIds = $catService->fetchTopicIdsInCategory($catId);
        }
        $recommendableTopicIds = $this->recommendation()->filterRecommendableTopicIds($allTopicIds);
        rsort($recommendableTopicIds);
        $page = $request->query->get('page', 1);
        $count = $request->query->get('count', 20);
        $offset = ($page - 1) * $count;
        $pageCount = ceil(count($recommendableTopicIds) / $count);
        $topicIds = array_slice($recommendableTopicIds, $offset, $count);
        $topics = ArrayUtility::mapByColumn($this->topic()->fetch($topicIds), 'id');
        krsort($topics);

        return $this->response('推荐次元分类', [
            'cats' => $cats,
            'curCatId' => $catId,
            'topics' => $topics,
            'pageCount' => $pageCount,
            'page' => $page,
            'count' => $count,
        ]);
    }

    /**
     * @Route("/topic_keyword")
     * @param Request $request
     * @return JsonResponse
     */
    public function topickeywordAction(Request $request) {
        $keyword = $request->query->get('keyword');
        $matchTopics = $this->topic()->fetchByKeywordOrderByPostCount($keyword, 0, 20);
        $topicName = array();
        foreach ($matchTopics as $topic) {
            $topicName[$topic->id] = $topic->title;
        }

        return new JsonResponse($topicName);
    }

    /**
     * @Route("/add_recommendable_topic")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addRecommendableTopic(Request $request) {
        $topicId = $request->request->get('topic_id');
        $this->recommendation()->addRecommendableTopics($topicId);

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/remove_recommendable_topic")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeRecommendableTopic(Request $request) {
        $topicId = $request->request->get('topic_id');
        $this->recommendation()->removeRecommendableTopics($topicId);

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/certified_topic/{page}", requirements={"page" = "\d+"})
     * @Template
     * @param int $page
     * @return array
     */
    public function certifiedTopicAction($page = 1) {
        $countPerPage = 20;
        list($topicIds, $topicsCount) = $this->topic()->fetchCertifiedTopics($page, $countPerPage);
        $topics = $this->topic()->fetch($topicIds);

        return $this->response('认证次元', [
            'topicIds' => $topicIds,
            'topics' => ArrayUtility::mapByColumn($topics, 'id'),
            'pageCount' => ceil($topicsCount / $countPerPage),
        ]);
    }

    /**
     * @Route("/certified_topic/remove")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeCertifiedTopic(Request $request) {
        $topicId = $request->request->get('topic_id');
        $referUrl = $request->headers->get('referer');
        try {
            $this->topic()->removeCertified($topicId);
        } catch (\Exception $e) {
            return $this->redirect($this->generateUrl('lychee_admin_topic_error', [
                'errorMsg' => '次元不存在',
                'callbackUrl' => $referUrl,
            ]));
        }

        return $this->redirect($referUrl);
    }

    /**
     * @Route("/certified_topic/add")
     * @Method("POST")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addCertifiedTopic(Request $request) {
        $topicId = $request->request->get('topic_id');
        $referUrl = $request->headers->get('referer');
        try {
            $this->topic()->maskAsCertified($topicId);
        } catch (\Exception $e) {
            return $this->redirect($this->generateUrl('lychee_admin_topic_error', [
                'errorMsg' => '次元不存在',
                'callbackUrl' => $referUrl,
            ]));
        }

        return $this->redirect($referUrl);
    }

	/**
	 * 次元推荐改为精选次元
	 * @Route("/featured_topic")
	 * @Template
	 * @return array
	 */
	public function featuredTopic() {
		$items = $this->recommendation()->listAllRecommendationTopics();
		$topicIds = array_map(function ($item) {
			/** @var RecommendationItem $item */
			return $item->getTargetId();
		}, $items);
		$topics = ArrayUtility::mapByColumn($this->topic()->fetch($topicIds), 'id');

		return $this->response('精选次元', [
			'items' => $items,
			'topics' => $topics,
		]);
	}

	/**
	 * @Route("/featured_topic/remove")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function removeFeaturedTopic(Request $request) {
		$id = $request->request->get('id');
		/**
		 * @var \Lychee\Module\Recommendation\Entity\RecommendationItem|null $item
		 */
		$item = $this->recommendation()->fetchItemById($id);

		if (null === $item) {
			throw $this->createNotFoundException();
		}
		$this->recommendation()->removeRecommendedItem($item);
		if ($item->getType() === RecommendationType::TOPIC) {
			$this->recommendation()->removeRecommendableTopics($item->getTargetId());
		}
		$this->clearCache();

		return new JsonResponse();
	}

	private function clearCache() {
		$this->container->get('memcache.default')->delete('rec_web');
		$this->container->get('memcache.default')->delete('rec_web2');
	}

	/**
	 * @Route("/featured_topic/create")
	 * @Method("POST")
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function createFeaturedTopic(Request $request) {
		$callbackUrl = $request->request->get('callback_url');
		$type = $request->request->get('type');
		$targetId = $request->request->get('target_id');
		$reason = $request->request->get('reason');
		$imageUrl = null;
		if ($request->files->has('image')) {
			$imageFile = $request->files->get('image');
			if ($imageFile) {
				$imageUrl = $this->storage()->setPrefix('recommendation/')->put($imageFile);
			}
		}
		if (RecommendationType::TOPIC === $type) {
			$topic = $this->topic()->fetchOne($targetId);
			if (null !== $topic) {
				if (!$imageUrl) {
					$imageUrl = $topic->indexImageUrl;
				}
				$target = $this->recommendation()->fetchItemByTarget($targetId, RecommendationType::TOPIC);
				if ($target) {
					return $this->redirectErrorPage('次元已存在于精选次元中, 无法重复添加', $request);
				}
				$this->recommendation()->addRecommendableTopics($topic->id);
			} else {
				return $this->redirect($this->generateUrl('lychee_admin_error', [
					'errorMsg' => '次元不存在, 请检查推荐ID',
					'callbackUrl' => $request->headers->get('referer'),
				]));
			}
		}
		$em = $this->getDoctrine()->getManager();
		$recommendation = new RecommendationItem();
		$recommendation->setTargetId($targetId)->setType($type)->setReason($reason)->setImage($imageUrl);
		$em->persist($recommendation);
		$em->flush();
		$this->clearCache();

		return $this->redirect($callbackUrl);
	}

	/**
	 * @Route("/featured_topic/switch")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function switchFeaturedTopicPosition(Request $request) {
		$ids = $request->request->get('ids');
		if (is_array($ids) && count($ids) == 2) {
			/** @var RecommendationItem $first */
			$first = $this->recommendation()->fetchItemById($ids[0]);
			/** @var RecommendationItem $second */
			$second = $this->recommendation()->fetchItemById($ids[1]);
			$tmpPosition = $second->getPosition();
			$second->setPosition($first->getPosition());
			$first->setPosition($tmpPosition);
			$em = $this->getDoctrine()->getManager();
			$em->flush();
		}

		return new JsonResponse();
	}

	/**
	 * @Route("/featured_topic/top")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function makeFeaturedTopicTop(Request $request) {
		$id = $request->request->get('id');
		$items = $this->recommendation()->listAllRecommendationTopics();
		$position = 1;
		foreach ($items as $item) {
			/** @var RecommendationItem $item */
			if ($item->getId() != $id) {
				$item->setPosition($position);
				$position += 1;
			} else {
				$item->setPosition(0);
			}
		}
		$em = $this->getDoctrine()->getManager();
		$em->flush();

		return $this->redirect($request->headers->get('referer'));
	}

	/**
	 * @Route("/featured_topic/bottom")
	 * @Method("POST")
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function makeFeaturedTopicBottom(Request $request) {
		$id = $request->request->get('id');
		$items = $this->recommendation()->listAllRecommendationTopics();
		$position = 0;
		$bottomItem = null;
		foreach ($items as $item) {
			/** @var RecommendationItem $item */
			if ($item->getId() != $id) {
				$item->setPosition($position);
				$position += 1;
			} else {
				$bottomItem = $item;
			}
		}
		$bottomItem->setPosition($position);
		$em = $this->getDoctrine()->getManager();
		$em->flush();

		return $this->redirect($request->headers->get('referer'));
	}
}
