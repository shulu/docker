<?php
namespace Lychee\Bundle\WebsiteBundle\Controller\Promotion;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class HuoYingController extends Controller {
    const PROMOTION_TOPIC_ID = 25186;

    /**
     * @Route("/huoying", name="huoying")
     * @Template("LycheeWebsiteBundle:Promotion:huoying.html.twig")
     */
    public function indexAction() {
        $postIds = $this->post()->fetchIdsWithImageByTopicId(
            self::PROMOTION_TOPIC_ID, 0, 15
        );

        $synthesizer = $this->synthesizerBuilder()
            ->buildListPostSynthesizer($postIds, 0);

        return array(
            'topicId' => self::PROMOTION_TOPIC_ID,
            'posts' => $synthesizer->synthesizeAll(),
            'closed' => false
        );
    }
} 