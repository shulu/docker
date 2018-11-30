<?php
namespace Lychee\Bundle\WebsiteBundle\Controller\Promotion;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class LoveLiveController extends Controller {

    const TOPIC_ID = 25978;

    /**
     * @Route("/chengdu_lovelive", name="chengdu_lovelive")
     * @Template("LycheeWebsiteBundle:Promotion:lovelive.html.twig")
     */
    public function indexAction() {
        $postIds = $this->post()->fetchIdsByTopicId(self::TOPIC_ID, 0, 9);
        $posts = $this->synthesizerBuilder()->buildListPostSynthesizer($postIds, 0)->synthesizeAll();

        return array(
            'topicId' => self::TOPIC_ID,
            'posts' => $posts
        );
    }
} 