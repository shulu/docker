<?php

namespace Lychee\Bundle\ApiBundle\Controller;
use Lychee\Component\ShuMei\ShuMei;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class ShuMeiController extends Controller{
    /**
     * @Route("/textfilter/check")
     * @Method("GET")
     * @ApiDoc(
     * )
     * @param Request $request
     *
     */
    public function TestAction(){
        $text = "水电费418868621注册 pan.baidu.com";
        $shumei = new ShuMei();
        return new JsonResponse( [$text,$shumei->Text($text)] );
    }
}