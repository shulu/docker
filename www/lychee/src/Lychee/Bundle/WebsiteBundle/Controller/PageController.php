<?php
namespace Lychee\Bundle\WebsiteBundle\Controller;

use Lychee\Bundle\CoreBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
class PageController extends Controller{
    /**
     * zhaomuAction
     *
     *
     * 获取每张图片的详细统计数据。
     * 根据选择的appId和app对应的广告账号列表，显示出指定时间段内，该app投放的广告中，每个素材的统计数据。
     *
     * @Route("/zm")
     * @Method("GET")
     * @Template()
     */
    public function zhaomuAction(){
        return [];
    }

    /**
     * h5Action
     *
     * h5模板页面
     *
     * @Route("/h5/{templateName}")
     * @Method("GET")
     */
    public function h5Action($templateName){
        $response = new Response();
        $data = [];
        $template = "LycheeWebsiteBundle:Page:h5/{$templateName}.html.twig";
        $content = $this->renderView($template, $data);
        $response->SetContent($content);
        return $response;
    }
}