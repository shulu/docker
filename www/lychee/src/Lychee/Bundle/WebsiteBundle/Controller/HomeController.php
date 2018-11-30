<?php

namespace Lychee\Bundle\WebsiteBundle\Controller;

use Lychee\Bundle\ApiBundle\DataSynthesizer\BannerSynthesizer;
use Lychee\Bundle\CoreBundle\Controller\Controller;
use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Component\Foundation\TopicUtility;
use Lychee\Module\Recommendation\Post\PredefineGroup;
use Lychee\Module\Recommendation\RecommendationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Lychee\Bundle\ApiBundle\DataSynthesizer\SynthesizerBuilder;

class HomeController extends Controller {

    /**
     * @Route("/", host="www.ciyo.cn")
     * @Route("/", host="ciyo.cn", name="homepage")
     * @Route("/", host="www.ciyocon.com")
     * @Route("/", host="ciyocon.com")
     * @Route("/", host="ciyo.test")
     * @Route("/", host="192.168.9.79")
     * @Route("/", host="dev.ciyo.work.net")
     */
    public function indexAction(Request $request) {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }

        $response = new Response();
//        $response->setPublic();
//        $lastModified = new \DateTime('2016-06-28 16:00:00');
//        $lastModified->setTimezone(new \DateTimeZone('UTC'));
//        $response->setLastModified($lastModified);
//        if ($response->isNotModified($request)) {
//            return $response;
//        }

        $banners = $this->recommendationBanner()->fetchAvailableBanners();
        $bannerData = array();
        foreach ($banners as $banner) {
            parse_str(substr($banner->url, 12), $query);
            if (!isset($query['action'])) {
                continue;
            }

            if ($query['action'] == 2 && $query['pid'] > 0) {
                $url = $this->generateUrl('share_post', array('postId' => $query['pid']));
            } else if ($query['action'] == 1 && $query['tid'] > 0) {
                $url = $this->generateUrl('topic_invite', array('topicId' => $query['tid']));
            } else {
                continue;
            }

            $bannerData[] = array(
                'title' => $banner->title,
                'url' => $url,
                'image' => $banner->imageUrl
            );
        }


        $groupManager = $this->get('lychee.module.recommendation.group_manager');
        $groups = $groupManager->getGroupsToShow();

        $groupPostsService = $this->get('lychee.module.recommendation.group_posts');
        $postIds = $groupPostsService->listPostIdsInGroup(PredefineGroup::ID_JINGXUAN, 0, 5, $nextCursor);
        $posts = $this->container->get('lychee_api.synthesizer_builder')
            ->buildBasicPostSynthesizer($postIds, 0)->synthesizeAll();
        $posts = array_values(array_filter($posts, function($p){
            return !isset($p['deleted']) || $p['deleted'] == false;
        }));
	    $posts = array_map(function($post) {
		    $post['topic']['color'] = isset($post['topic']['color']) ? TopicUtility::filterColor($post['topic']['color']) : '';
		    return $post;
	    }, $posts);

        if ($nextCursor != 0) {
            $nextUrl = '/home_posts?cursor='.$nextCursor.'&group='.urlencode('精选');
        } else {
            $nextUrl = 0;
        }

        $viewData = array(
            'banners' => $bannerData,
            'groups' => $groups,
            'posts' => $posts,
            'nextUrl' => $nextUrl
        );

        $userAgent = $request->server->get('HTTP_USER_AGENT');
        if (!$request->query->has('mobile') && ($request->query->has('desktop') || !preg_match('/iPhone|iPad|iPod|iOS|android/i', $userAgent))) {
            $template = 'LycheeWebsiteBundle:Home:index.html.twig';
            $items = $this->recommendation()->listRecommendedItems(RecommendationType::TOPIC, 0, 9, $nextTopicCursor);
            $topicIds = array();
            foreach ($items as $item) {
                $topicIds[] = $item->getTargetId();
            }
            $viewData['topics'] = $this->topic()->fetch($topicIds);
        } else {
            $template = 'LycheeWebsiteBundle:Home:index-mobile.html.twig';
            $subjects = $this->specialSubject()->fetchSpecialSubjectByCursor(0, 5, $nextCursor);
            if (!$subjects) {
                $subjects = array();
            }
            $viewData['subjects'] = $subjects;
        }

        $content = $this->renderView($template, $viewData);
        $response->setContent($content);
        return $response;
    }

    /**
     * @Route("/home_posts")
     * @Template("LycheeWebsiteBundle:Home:posts.html.twig")
     */
    public function homeGetPostsAction(Request $request) {
        $groupName = $request->query->get('group');
        $cursor = $request->query->getInt('cursor', 0);

        $groupManager = $this->get('lychee.module.recommendation.group_manager');
        $group = $groupManager->getGroupByName($groupName);
        if ($group == null) {
            return new Response('', 200);
        }

        $groupPostsService = $this->get('lychee.module.recommendation.group_posts');
        $postIds = $groupPostsService->listPostIdsInGroup($group->id(), $cursor, 5, $nextCursor);
        $posts = $this->container->get('lychee_api.synthesizer_builder')
            ->buildBasicPostSynthesizer($postIds, 0)->synthesizeAll();
        $posts = array_values(array_filter($posts, function($p){
            return !isset($p['deleted']) || $p['deleted'] == false;
        }));

        $response = new Response();
        if ($nextCursor != 0) {
            $nextUrl = '/home_posts?cursor='.$nextCursor.'&group='.urlencode($groupName);
            $response->headers->set('CY-NextUrl', $nextUrl);
        }

        if ($request->query->has('mobile')) {
            $subjects = $this->specialSubject()->fetchSpecialSubjectByCursor(0, 5, $nextCursor);
            if (!$subjects) {
                $subjects = array();
            }
            return $this->render('LycheeWebsiteBundle:Home:timeline-mobile.html.twig', array('posts' => $posts, 'subjects' => $subjects), $response);
        } else {
            return $this->render('LycheeWebsiteBundle:Home:posts.html.twig', array('posts' => $posts), $response);
        }
    }

    /**
     * @Route("/products", name="products")
     * @Template("LycheeWebsiteBundle:Home:products.html.twig")
     */
    public function productsAction(Request $request) {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }
        return array();
    }

    /**
     * @Route("/ciyonya", name="ciyonya")
     * @Template("LycheeWebsiteBundle:Home:ciyonya.html.twig")
     */
    public function ciyonyaAction() {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }

        $skins = array(
            array('name'=>'次元娘冷到发抖', 'img'=>'http://dl.pinyin.sogou.com/cache/skins/uploadImage/2016/01/07/14521546732722_former.gif', 'url'=>'http://download.pinyin.sogou.com/skins/download.php?skin_id=520357&rf=detail', ),
            array('name'=>'次元娘看电影', 'img'=>'http://dl.pinyin.sogou.com/cache/skins/uploadImage/2016/01/06/14520776214715_former.gif', 'url'=>'http://download.pinyin.sogou.com/skins/download.php?skin_id=520315&rf=detail', ),
            array('name'=>'次元娘猴年大吉', 'img'=>'http://dl.pinyin.sogou.com/cache/skins/uploadImage/2016/01/06/14520536718221_former.gif', 'url'=>'http://download.pinyin.sogou.com/skins/download.php?skin_id=520286&rf=detail', ),
            array('name'=>'次元娘超冷漠', 'img'=>'http://dl.pinyin.sogou.com/cache/skins/uploadImage/2016/01/05/14519820479805_former.gif', 'url'=>'http://download.pinyin.sogou.com/skins/download.php?skin_id=520230&rf=detail', ),
            array('name'=>'次元娘加鸡腿', 'img'=>'http://dl.pinyin.sogou.com/cache/skins/uploadImage/2015/12/14/14500860875642_former.gif', 'url'=>'http://download.pinyin.sogou.com/skins/download.php?skin_id=519287&rf=detail', ),
            array('name'=>'次元娘 开卷有睡意', 'img'=>'http://dl.pinyin.sogou.com/cache/skins/uploadImage/2015/11/30/14488712599505_former.gif', 'url'=>'http://download.pinyin.sogou.com/skins/download.php?skin_id=518634&rf=detail', ),
            array('name'=>'次元娘踩踩踩', 'img'=>'http://dl.pinyin.sogou.com/cache/skins/uploadImage/2015/11/26/14485059044523_former.gif', 'url'=>'http://download.pinyin.sogou.com/skins/download.php?skin_id=518459&rf=detail', ),
            array('name'=>'次元娘泡泡浴', 'img'=>'http://dl.pinyin.sogou.com/cache/skins/uploadImage/2015/11/23/14482495465095_former.gif', 'url'=>'http://download.pinyin.sogou.com/skins/download.php?skin_id=518301&rf=detail', ),
            array('name'=>'次元娘圣诞节', 'img'=>'http://dl.pinyin.sogou.com/cache/skins/uploadImage/2015/11/06/14467965755532_former.gif', 'url'=>'http://download.pinyin.sogou.com/skins/download.php?skin_id=517622&rf=detail', ),
            array('name'=>'次元娘睡懒觉', 'img'=>'http://dl.pinyin.sogou.com/cache/skins/uploadImage/2016/01/08/14522387963084_former.gif', 'url'=>'http://download.pinyin.sogou.com/skins/download.php?skin_id=520406&rf=detail', ),
            array('name'=>'次元娘很生气', 'img'=>'http://dl.pinyin.sogou.com/cache/skins/uploadImage/2015/12/21/14506893168008_former.gif', 'url'=>'http://download.pinyin.sogou.com/skins/download.php?skin_id=519581&rf=detail', ),
            array('name'=>'次元娘涮火锅', 'img'=>'http://dl.pinyin.sogou.com/cache/skins/uploadImage/2015/11/06/14467918243315_former.gif', 'url'=>'http://download.pinyin.sogou.com/skins/download.php?skin_id=517563&rf=detail', ),
            array('name'=>'次元娘吃货', 'img'=>'http://dl.pinyin.sogou.com/cache/skins/uploadImage/2015/11/06/14467918243315_former.gif', 'url'=>'http://download.pinyin.sogou.com/skins/download.php?skin_id=517563&rf=detail', ),
            array('name'=>'奔跑的次元娘', 'img'=>'http://dl.pinyin.sogou.com/cache/skins/uploadImage/2015/11/05/14466932003272_former.gif', 'url'=>'http://download.pinyin.sogou.com/skins/download.php?skin_id=516519&rf=detail', ),
        );

        $wallpapers = array(
            array('name' => '春眠不觉晓', 'url' => 'http://qn.ciyocon.com/wallpapers/chun-mian-bu-jue-xiao.png'),
            array('name' => '女仆装', 'url' => 'http://qn.ciyocon.com/wallpapers/nv-pu-zhuang.png'),
            array('name' => 'pocketmon go', 'url' => 'http://qn.ciyocon.com/wallpapers/pm-go.jpg'),
            array('name' => '夏天好热', 'url' => 'http://qn.ciyocon.com/wallpapers/xia-tian-hao-re.png'),
            array('name' => '下雨天', 'url' => 'http://qn.ciyocon.com/wallpapers/xia-yu-tian.jpg'),
            array('name' => '新春', 'url' => 'http://qn.ciyocon.com/wallpapers/xin-chun.png'),
        );

        $works = array(
            array('img' => 'http://qn.ciyocon.com/upload/Fg0rgT0sih_lPFjJduvLtuX0rWg8', 'userId' => 439170),
            array('img' => 'http://qn.ciyocon.com/upload/FmPhRuB3Voy3emPN2VLnjrHzd7Nq', 'userId' => 439170),
            array('img' => 'http://qn.ciyocon.com/upload/Fjy4HyXXmlaC26HTge_CHhYtzz-1', 'userId' => 439170),
            array('img' => 'http://qn.ciyocon.com/upload/Fh4fyzJOZzIC8y6oQw-qJXhH6993', 'userId' => 439170),
            array('img' => 'http://qn.ciyocon.com/upload/Fp1rVEcv77zIoR5bm21dGCN-Ihnt', 'userId' => 439170),
            array('img' => 'http://qn.ciyocon.com/upload/Fo0iD7qxDTPIQt-Wf0hziVXoHk5N', 'userId' => 439170),
            array('img' => 'http://qn.ciyocon.com/upload/FjjF1zAL6ZRZwdHu4LtxEly2HTMC', 'userId' => 439170),
            array('img' => 'http://qn.ciyocon.com/upload/FhjNbnIB2M5FVOgX9Nyy82xr-Sv1', 'userId' => 439170),
            array('img' => 'http://qn.ciyocon.com/upload/FgjaQXLHWBCoiCHAHV8jBe5a1H2U', 'userId' => 439170),
            array('img' => 'http://qn.ciyocon.com/upload/FknooJez6ULV1BF7aEKkrMkpvrGF', 'userId' => 59403),
            array('img' => 'http://qn.ciyocon.com/upload/FuoNhcJcDZENrYf4PVfPPlf7BVAW', 'userId' => 59403),
            array('img' => 'http://qn.ciyocon.com/upload/FmzbZyyKjR60wgTIcQbbA4SEqoAb', 'userId' => 59403),
            array('img' => 'http://qn.ciyocon.com/upload/FkC4BNHtTfAZO87NwhAPbmjmyXHo', 'userId' => 59403),
            array('img' => 'http://qn.ciyocon.com/upload/FkzxPOHgMj-jQGfqkoWTpgSxkeGD', 'userId' => 189513),
            array('img' => 'http://qn.ciyocon.com/upload/Fr-HcvnsD2UDi_hh0vfsarJ9XUXy', 'userId' => 149078),
            array('img' => 'http://qn.ciyocon.com/upload/FudwTjjo_LCBkV0PI-eJImYcFXGg', 'userId' => 265134),
            array('img' => 'http://qn.ciyocon.com/upload/Fsg1sQ7Dpi_cXKYCq3ezXBC2S7xM', 'userId' => 265134),
            array('img' => 'http://qn.ciyocon.com/upload/Fk7Rb20yOX1AwNIWSzQ01lv1q90i', 'userId' => 265134),
            array('img' => 'http://qn.ciyocon.com/upload/FhQr3gMDtHN9nMVUGB47W49D7Pdj', 'userId' => 141641),
            array('img' => 'http://qn.ciyocon.com/upload/FvVodUy_kDMDiFvbZ6FXuKKRsGal', 'userId' => 356957),
            array('img' => 'http://qn.ciyocon.com/upload/FjcrEu2W77qz-X9CqxVuFmxMWKjo', 'userId' => 193989),
            array('img' => 'http://qn.ciyocon.com/upload/FuMOsh3Qk0fVy14DJtnurPXmo95g', 'userId' => 661409),
            array('img' => 'http://qn.ciyocon.com/upload/FrS8sUm6URxXRCyTUOrerVZGkIKx', 'userId' => 37265),
            array('img' => 'http://qn.ciyocon.com/upload/Fs4b1LHolAx2IeAmPQxFQLWso1M7', 'userId' => 250289),
            array('img' => 'http://qn.ciyocon.com/upload/FjZV7wzk4GK_wxpwreQK97JIPDCK', 'userId' => 426998),
            array('img' => 'http://qn.ciyocon.com/upload/FkwyuBdnVleiKz1sAOgVJfL3Fs32', 'userId' => 184824),
            array('img' => 'http://qn.ciyocon.com/upload/FkngY2os0zNDOS0FjsSXvsBfar-g', 'userId' => 101363),
            array('img' => 'http://qn.ciyocon.com/upload/Fg8x-oQlNWBoYLR-iJqgJ35OBBs1', 'userId' => 186331),
            array('img' => 'http://qn.ciyocon.com/upload/FpbFnl5682F6aJCL6kkFlUzxz60q', 'userId' => 186331),
            array('img' => 'http://qn.ciyocon.com/upload/FhA1Q0YX-rFSCUbHvmcrm-IDoocD', 'userId' => 348765),
            array('img' => 'http://qn.ciyocon.com/upload/FmvRXYZJUhWzhbpr0ONxs0BJonIU', 'userId' => 359831),
            array('img' => 'http://qn.ciyocon.com/upload/FuvF59tC7mtB4pVw3dUMnk5W_ro_', 'userId' => 755269),
            array('img' => 'http://qn.ciyocon.com/upload/FjdqvJJggwS-tdLWW3cctQeWABrc', 'userId' => 755269),
            array('img' => 'http://qn.ciyocon.com/upload/FiYCunrULF1x70XGk8YMCsxgMkpq', 'userId' => 759084),
        );

        $userIds = array_unique(ArrayUtility::columns($works, 'userId'));
        $users = $this->account()->fetch($userIds);
        foreach ($works as &$work) {
            if (isset($users[$work['userId']])) {
                $work['avatar'] = $users[$work['userId']]->avatarUrl;
                $work['nickname'] = $users[$work['userId']]->nickname;
            }
        }
        unset($work);

        return array('wallpapers' => $wallpapers, 'skins' => $skins, 'works' => $works);
    }

    /**
     * @Route("/join-us", name="join-us")
     * @Template("LycheeWebsiteBundle:Home:join-us.html.twig")
     */
    public function joinUsAction() {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }
        return array();
    }

    /**
     * @Route("/about-us", name="about-us")
     * @Template("LycheeWebsiteBundle:Home:about-us.html.twig")
     */
    public function aboutUsAction() {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }
        return array();
    }

    /**
     * @Route("/about")
     * @Template("LycheeWebsiteBundle:Home:about.html.twig")
     */
    public function aboutAction() {
        return $this->redirect($this->generateUrl('about-us'));
    }

    /**
     * @Route("/emoticon/about")
     * @Template
     */
    public function emoticonAboutAction() {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }
        return [];
    }

    /**
     * @return SynthesizerBuilder
     */
    private function synthesizerBuilder() {
        return $this->container->get('lychee_api.synthesizer_builder');
    }

    private function getHotPostsData() {
        $iterator = $this->recommendation()->getHotestIdList(RecommendationType::POST)
            ->getIterator();
        $iterator->setCursor(0);
        $iterator->setStep(6);
        $postIds = $iterator->current();

        $synthesizer = $this->synthesizerBuilder()
            ->buildListPostSynthesizer($postIds, 0);
        return $synthesizer->synthesizeAll();
    }

	/**
	 * @Route("/message", host="rekallstudio.com")
	 * @Route("/message", host="unknownmessage.test")
	 * @Route("/message", host="192.168.2.38")
	 * @Template
	 */
    public function unknownMessageAction() {
    	return [];
    }
}
