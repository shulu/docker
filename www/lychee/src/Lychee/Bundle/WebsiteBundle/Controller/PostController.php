<?php
namespace Lychee\Bundle\WebsiteBundle\Controller;

use Lychee\Bundle\CoreBundle\Controller\Controller;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Recommendation\RecommendationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Lychee\Bundle\ApiBundle\DataSynthesizer\SynthesizerBuilder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PostController extends Controller {


    /**
     * @Route("/web/{postId}")
     * @Route("/posts/{postId}/share", name="share_post")
     * @Method("GET")
     * @Template()
     */
    public function shareAction($postId) {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }
        $postId = intval($postId);
        if ($postId <= 0) {
            throw $this->createNotFoundException();
        }

        $post = $this->getPostData($postId);
        if ($post == null || (isset($post['deleted']) && $post['deleted'])
            || (isset($post['topic']['private']) && $post['topic']['private'])) {
            throw $this->createNotFoundException();
        }

        $comments = $this->getPostCommentsData($postId);
        $annotation = isset($post['annotation']) ? $post['annotation'] : new \stdClass();
        if (isset($annotation->multi_photos) ) {
            $images = $annotation->multi_photos;
        } else if (isset($post['image_url']) && !empty($post['image_url'])) {
            $images = array($post['image_url']);
        } else {
            $images = array();
        }

        $link = array();
        if (isset($annotation->resource_link) && isset($annotation->resource_title)) {
            $link['url'] = $annotation->resource_link;
            $link['title'] = $annotation->resource_title;
        }

        $supportedPostType = isset($post['type']) && in_array($post['type'], array(
                'picture', 'video', 'resource', 'voting', 'schedule', 'short_video'));

        $result = array(
            'post' => $post,
            'supportedPostType' => $supportedPostType,
            'images' => $images,
            'link' => $link,
            'comments' => $comments,
            'downloadLink' => $this->generateUrl(
                'download_app', array(), UrlGeneratorInterface::ABSOLUTE_URL
            )
        );

        return $result;
    }
    /**
     * @Route("/posts/videoshare/{postId}")
     * @Method("GET")
     * @Template()
     */
    public function videoShareAction($postId){

        return $postId;
    }

    /**
     * @Route(
     *     "/posts/homeshare/{userId}",
     *     requirements={"userId":"\d+"}
     * )
     * @Method("GET")
     * @Template()
     */
    public function homeShareAction($userId){
        //头像，昵称，等级，性别，住址，年龄，签名，粉丝数，关注数
        //我最近6个帖子【发帖时间，内容，关注数，评论数，所在次元名字】
        //我最近6个视频【封面，关注数】

        $user = $this->account()->fetchOne($userId);

        if ($user === null) {
            $userId=31721;
            $user = $this->account()->fetchOne($userId);
        }

        $synthesizerBuilder = $this->container->get('lychee_api.synthesizer_builder');

        $synthesizer = $synthesizerBuilder
            ->buildProfiledUserSynthesizer(array($user),  0);
        $data = $synthesizer->synthesizeOne($user->id);

        //查看别人的帖子列表时只显示在公开次元内的
        $postIds = $this->post()->fetchIdsByAuthorIdInPublicTopic(
            $userId, 0 , 10
        );
        $synthesizer = $synthesizerBuilder
            ->buildListPostSynthesizer($postIds, 0);
        $postInfos = $synthesizer->synthesizeAll();
        $postInfos = $this->filterUndeletedPosts($postInfos);
        $postInfos = array_slice($postInfos,0 , 6);

        $postIds = $this->post()->fetchShortVideoIdsByAuthorId($userId,0, 6);

        $synthesizer = $synthesizerBuilder
            ->buildListPostSynthesizer($postIds, 0);
        $videoInfos = $synthesizer->synthesizeAll();
        $videoInfos = $this->filterUndeletedPosts($videoInfos);
        $videoInfos = array_slice($videoInfos,0 , 6);

        $return = [
            'userData'=>$data,
            'posts' => $postInfos,
            'videos' => $videoInfos,
        ];
        return $return;
    }
    private function filterUndeletedPosts($data) {
        return array_values(array_filter($data, function($p){
            return !isset($p['deleted']) || $p['deleted'] == false;
        }));
    }

    /**
     * @return SynthesizerBuilder
     */
    private function synthesizerBuilder() {
        return $this->container->get('lychee_api.synthesizer_builder');
    }

    private function getPostData($postId) {
        $post = $this->post()->fetchOne($postId);
        if ($post == null) {
            return null;
        }

        $topic = $this->topic()->fetchOne($post->topicId);
        if ($topic == null) {
            return null;
        }

        $synthesizer = $this->synthesizerBuilder()
            ->buildListPostSynthesizer(array($post), 0);
        return $synthesizer->synthesizeOne($postId);
    }

    private function getPostCommentsData($postId) {
        $commentIdsByPostId = $this->comment()->fetchLatestIdsByPostIds(array($postId), 5);
        $commentIds = array_reverse($commentIdsByPostId[$postId]);
        $synthesizer = $this->synthesizerBuilder()
            ->buildSimpleCommentSynthesizer($commentIds, 0);
        return $synthesizer->synthesizeAll();
    }

} 