<?php

namespace Lychee\Bundle\AdminBundle\Controller;

use Lychee\Bundle\CoreBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $modules = array();
        $roles = $this->getUser()->getRoles();
        foreach ($roles as $role) {
            $modules[] = array(
                $this->generateUrl($role->routeName),
                $role->name
            );
        }

        if ($request->getHost() == 'servicio.rekallstudio.com' || $request->getHost() == 'localhost') {
            $module = [
                $this->generateUrl('lychee_admin_extramessage_index'),
                '异次元通讯'
            ];
            $modules[] = $module;
        }


        return array(
            'modules' => $modules
        );
        //return array();
    }

    /**
     * @Route("/parse_html")
     */
    public function parseLinkAction(Request $request)
    {
        $url = $request->query->get('url');
        if (!$url) {
            return new JsonResponse();
        }
        $crawler = new Crawler(file_get_contents($url));

        $title = $crawler->filter('#header > h1')->eq(0)->text();
        $summary = trim($crawler->filter('#Summary')->eq(0)->text());
        $videoCoverLinkNode = $crawler->filter('#video > video');
        $coverUrl = null;
        if (count($videoCoverLinkNode)) {
            $coverUrl = $videoCoverLinkNode->attr('poster');
            $linkType = 'video';
            $mediaUrl = $videoCoverLinkNode->attr('src');
        } else {
            $linkType = 'others';
        }
        if (!$coverUrl) {
            $coverLinkNode = $crawler->filter('#content')->children()->filter('img');
            if (count($coverLinkNode)) {
                $coverUrl = $coverLinkNode->eq(0)->attr('src');
            } else {
                $coverUrl = '';
            }
        }

        return new JsonResponse(compact('title', 'summary', 'coverUrl', 'linkType', 'mediaUrl'));
    }

    /**
     * @Route("/topic_keyword")
     * @param Request $request
     * @return JsonResponse
     */
    public function topickeywordAction(Request $request)
    {
        $keyword = $request->query->get('keyword');
        $matchTopics = $this->topic()->fetchByKeywordOrderByPostCount($keyword, 0, 20);
        $topicName = array();
        foreach ($matchTopics as $topic) {
            $topicName[] = $topic->title;
        }

        return new JsonResponse($topicName);
    }
}
