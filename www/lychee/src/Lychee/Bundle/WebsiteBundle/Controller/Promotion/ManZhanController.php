<?php
namespace Lychee\Bundle\WebsiteBundle\Controller\Promotion;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class ManZhanController extends Controller {

    const PROMOTION_TOPIC_ID = 25617;

    /**
     * @Route("/ciyuanjiang_manzhan", name="ciyuanjiang_manzhan")
     * @Template("LycheeWebsiteBundle:Promotion:manzhan.html.twig")
     */
    public function manzhanAction() {
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

    /**
     * @Route("/ciyuanjiang_manzhan_closed", name="ciyuanjiang_manzhan_closed")
     * @Template("LycheeWebsiteBundle:Promotion:manzhan.html.twig")
     */
    public function manzhanClosed() {
        //活动结束，获奖名单
        $postIds = array(
            8507451539457,
            8788225291265,
            8487019853825,
            8503458501633,
            8960835048449,
            8977051168769,
            8791203123201,
            8971533620225,
            8587005021185,
            8604433505281,
            8745919409153,
            8787118133249,
            8588613733377,
            8836417724417,
            8828033715201,
        );

        $synthesizer = $this->synthesizerBuilder()
            ->buildListPostSynthesizer($postIds, 0);

        return array(
            'topicId' => self::PROMOTION_TOPIC_ID,
            'posts' => $synthesizer->synthesizeAll(),
            'closed' => true
        );
    }
} 