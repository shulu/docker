<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Constant;
use Lychee\Module\ContentManagement\SearchType;
use Lychee\Module\Recommendation\RecommendationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Lychee\Module\Search\Searcher;
use Lychee\Module\ContentManagement\SearchKeywordManagement;
use Lychee\Module\Search\TopicPostSearcher;
use Lychee\Module\Search\TopicFollowerSearcher;

class SearchController extends Controller {

    /**
     * @return Searcher
     */
    private function topicSearcher() {
        return $this->get('lychee.module.search.topicSearcher');
    }

    /**
     * @return Searcher
     */
    private function superiorTopicSearcher() {
        return $this->get('lychee.module.search.superiorTopicSearcher');
    }

    /**
     * @return Searcher
     */
    private function superiorPostSearcher() {
        return $this->get('lychee.module.search.superiorPostSearcher');
    }

    /**
     * @return Searcher
     */
    private function superiorResourcePostSearcher() {
        return $this->get('lychee.module.search.superiorResourcePostSearcher');
    }

    /**
     * @return Searcher
     */
    private function accountSearcher() {
        return $this->get('lychee.module.search.accountSearcher');
    }

    /**
     * @return Searcher
     */
    private function postSearcher() {
        return $this->get('lychee.module.search.postSearcher');
    }

    /**
     * @return Searcher
     */
    private function resourcePostSearcher() {
        return $this->get('lychee.module.search.resourcePostSearcher');
    }

    /**
     * @return TopicPostSearcher
     */
    private function topicPostSearcher() {
        return $this->get('lychee.module.search.topicPostSearcher');
    }

    /**
     * @return TopicFollowerSearcher
     */
    private function topicFollowerSearcher() {
        return $this->get('lychee.module.search.topicFollowerSearcher');
    }

    private function sortByHots($ids, $type) {
        $hotIds = $this->recommendation()->fetchHottestIds($type, 0, 200, $nextCursor);
        return ArrayUtility::sortIntsByIntArray($ids, $hotIds);
    }

    private function containingSensitiveWord($query) {
//        $sensitiveWords = array('战', '舰', '娘', 'collection', '少女', '东京', '喰种', '食尸', '食种');
//        foreach ($sensitiveWords as $word) {
//            if (stripos($query, $word) !== false) {
//                return true;
//            }
//        }

        return false;
    }

    /**
     * @Route("/search")
     * @Method("GET")
     * @ApiDoc(
     *   section="search",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="query", "dataType"="string", "required"=true},
     *     {"name"="channel", "dataType"="string", "required"=false,
     *       "description"="渠道"},
     *     {"name"="client", "dataType"="string", "required"=false,
     *       "description"="客户端类型"}
     *   }
     * )
     */
    public function searchAll(Request $request) {
        $account = $this->requireAuth($request);
        $query = $this->requireParam($request->query, 'query');
        if (strlen($query) >= 50 || $this->containingSensitiveWord($query)) {
            return $this->dataResponse(array(
                'topics' => array(), 'more_topic' => false,
                'users' => array(), 'more_user' => false,
                'posts' => array(), 'more_post' => false
            ));
        }

        $clientVersion = $request->query->get(self::CLIENT_APP_VERSION_KEY);
        if ($clientVersion && version_compare($clientVersion, '2.0', '<')) {
            list($topicCount, $postCount, $userCount) = array(3, 3, 3);
        } else {
            list($topicCount, $postCount, $userCount) = array(5, 1, 1);
        }

        $topicIds = $this->searchTopics($query, 0, $topicCount, $topicTotal, $request);
        $moreTopic = $topicTotal > $topicCount;
        $topicIds = $this->filterBlockingTopics($request, $topicIds);
        $topicIds = $this->sortByHots($topicIds, RecommendationType::TOPIC);
        $topicSynthesizer = $this->getSynthesizerBuilder()
            ->buildSimpleTopicSynthesizer($topicIds, $account ? $account->id : 0);
        if (is_numeric($query)) {
           $userCount -= 1;
        }
        $userIds = $this->accountSearcher()->search($query, 0, $userCount, $userTotal);
        $moreUser = $userTotal > $userCount;
        if (is_numeric($query)) {
            array_unshift($userIds, $query);
            $userTotal += 1;
        }
        $userSynthesizer = $this->getSynthesizerBuilder()
            ->buildUserSynthesizer($userIds, $account ? $account->id : 0);

        $postIds = $this->searchPosts($query, 0, $postCount, $postTotal, $request);
        $morePost = $postTotal > $postCount;
        $postIds = $this->sortByHots($postIds, RecommendationType::POST);
        $postSynthesizer = $this->getSynthesizerBuilder()
            ->buildListPostSynthesizer($postIds, $account ? $account->id : 0);

        $response = array(
            'topics' => $topicSynthesizer->synthesizeAll(),
            'more_topic' => $moreTopic,
            'topic_total' => $topicTotal,
            'users' => $userSynthesizer->synthesizeAll(),
            'more_user' => $moreUser,
            'user_total' => $userTotal,
            'posts' => $postSynthesizer->synthesizeAll(),
            'more_post' => $morePost,
            'post_total' => $postTotal
        );
        return $this->dataResponse($response);
    }


    /**
     * 通过请求的参数判断是否只展示优质内容
     *
     * @param Request $request
     * @return bool
     */
    private function isLimitSuperior(Request $request)
    {
        $client = $request->query->get(self::CLIENT_PLATFORM_KEY);
        if ('ios' == strtolower($client)) {
            return true;
        }
        $channel = $request->query->get(self::CLIENT_CHANNEL_KEY);
        if ('xiaomi' == strtolower($channel)) {
            return true;
        }
        return false;
    }

    private function searchTopics($query, $offset = 0, $limit = 20, &$total, Request $request)
    {
        if ($this->isLimitSuperior($request)) {
            return $this->superiorTopicSearcher()->search($query, $offset, $limit, $total);
        }

        return $this->topicSearcher()->search($query, $offset, $limit, $total);
    }

    private function searchPosts($query, $offset = 0, $limit = 20, &$total, Request $request)
    {
        if ($this->isLimitSuperior($request)) {
            return $this->superiorPostSearcher()->search($query, $offset, $limit, $total);
        }

        return $this->postSearcher()->search($query, $offset, $limit, $total);
    }

    private function searchResourcePosts($query, $offset = 0, $limit = 20, &$total, Request $request)
    {
        if ($this->isLimitSuperior($request)) {
            return $this->superiorResourcePostSearcher()->search($query, $offset, $limit, $total);
        }

        return $this->resourcePostSearcher()->search($query, $offset, $limit, $total);
    }

    /**
     * @return SearchKeywordManagement
     */
    private function getKeywordRecorder() {
        return $this->get('lychee.module.content_management.search_keyword');
    }

    /**
     * @Route("/search/{type}", requirements={"type": "(topic|user|post|resource)"})
     * @Method("GET")
     * @ApiDoc(
     *   section="search",
     *   requirements={
     *     {"name"="type", "dataType"="string", "description"="可以为'topic','user','post','resource'"}
     *   },
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="query", "dataType"="string", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"},
     *     {"name"="channel", "dataType"="string", "required"=false,
     *       "description"="渠道"},
     *     {"name"="client", "dataType"="string", "required"=false,
     *       "description"="客户端类型"}
     *   }
     * )
     */
    public function search(Request $request, $type) {
        $account = $this->requireAuth($request);
        $query = $this->requireParam($request->query, 'query');
        if (strlen($query) >= 50 || $this->containingSensitiveWord($query)) {
            return $this->arrayResponse('resource' ? 'posts': $type.'s', array(), 0);
        }
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);

        if ($type == 'topic') {
            $this->getKeywordRecorder()->record($query, SearchType::TOPIC, $account->id);
            $targetIds = $this->searchTopics($query, $cursor, $count, $total, $request);
            $targetIds = $this->filterBlockingTopics($request, $targetIds);
            $targetIds = ArrayUtility::diffValue($targetIds, array(Constant::SHENYEFULI_TOPIC_ID));
            $targetIds = $this->sortByHots($targetIds, RecommendationType::TOPIC);
            $synthesizer = $this->getSynthesizerBuilder()
                ->buildSimpleTopicSynthesizer($targetIds, $account->id);
        } else if ($type == 'user') {
            $this->getKeywordRecorder()->record($query, SearchType::USER, $account->id);
            $targetIds = $this->accountSearcher()->search($query, $cursor, $count, $total);
            $synthesizer = $this->getSynthesizerBuilder()
                ->buildUserSynthesizer($targetIds, $account->id);
        } else if ($type == 'resource') {
            $this->getKeywordRecorder()->record($query, SearchType::RESOURCE_POST, $account->id);
            $targetIds = $this->searchResourcePosts($query, $cursor, $count, $total, $request);
            $targetIds = $this->sortByHots($targetIds, RecommendationType::POST);
            $synthesizer = $this->getSynthesizerBuilder()
                ->buildListPostSynthesizer($targetIds, $account->id);
        } else {
            $this->getKeywordRecorder()->record($query, SearchType::POST, $account->id);
            $targetIds = $this->searchPosts($query, $cursor, $count, $total, $request);
            $synthesizer = $this->getSynthesizerBuilder()
                ->buildListPostSynthesizer($targetIds, $account->id);
        }

        if (count($targetIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $cursor + $count;
        }

        $key = $type == 'resource' ? 'posts': $type.'s';
        $result = array(
            $key => $synthesizer->synthesizeAll(),
            'next_cursor' => strval($nextCursor)
        );
        if (isset($total)) {
            $result['total'] = $total;
        }

        return $this->dataResponse($result);
    }

    /**
     * @Route("/search/post_in_topic")
     * @Method("GET")
     * @ApiDoc(
     *   section="search",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="topic", "dataType"="integer", "required"=true},
     *     {"name"="query", "dataType"="string", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function searchPostInTopic(Request $request) {
        $account = $this->requireAuth($request);
        $query = $this->requireParam($request->query, 'query');
        if (strlen($query) >= 50 || $this->containingSensitiveWord($query)) {
            return $this->arrayResponse('posts', array(), 0);
        }
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);
        $topicId = $this->requireId($request->query, 'topic');

        $topicPostSearcher = $this->topicPostSearcher();
        $topicPostSearcher->setTopicId($topicId);
        $postIds = $topicPostSearcher->search($query, $cursor, $count, $total);
        $synthesizer = $this->getSynthesizerBuilder()
            ->buildListPostSynthesizer($postIds, $account->id);

        if (count($postIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $cursor + $count;
        }

        $result = array(
            'posts' => $synthesizer->synthesizeAll(),
            'next_cursor' => strval($nextCursor)
        );
        if (isset($total)) {
            $result['total'] = $total;
        }

        return $this->dataResponse($result);
    }

    /**
     * @Route("/search/user_in_topic")
     * @Method("GET")
     * @ApiDoc(
     *   section="search",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="topic", "dataType"="integer", "required"=true},
     *     {"name"="query", "dataType"="string", "required"=true},
     *     {"name"="cursor", "dataType"="integer", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function searchUserInTopic(Request $request) {
        $account = $this->requireAuth($request);
        $query = $this->requireParam($request->query, 'query');
        if (strlen($query) >= 50 || $this->containingSensitiveWord($query)) {
            return $this->arrayResponse('posts', array(), 0);
        }
        list($cursor, $count) = $this->getCursorAndCount($request->query, 20, 50);
        $topicId = $this->requireId($request->query, 'topic');

        $searcher = $this->topicFollowerSearcher();
        $searcher->setTopicId($topicId);
        $userIds = $searcher->search($query, $cursor, $count, $total);
        $synthesizer = $this->getSynthesizerBuilder()
            ->buildUserSynthesizer($userIds, $account->id);

        if (count($userIds) < $count) {
            $nextCursor = 0;
        } else {
            $nextCursor = $cursor + $count;
        }

        $result = array(
            'users' => $synthesizer->synthesizeAll(),
            'next_cursor' => strval($nextCursor)
        );
        if (isset($total)) {
            $result['total'] = $total;
        }

        return $this->dataResponse($result);
    }

}