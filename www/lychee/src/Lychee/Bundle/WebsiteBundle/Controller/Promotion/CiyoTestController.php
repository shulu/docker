<?php
namespace Lychee\Bundle\WebsiteBundle\Controller\Promotion;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class CiyoTestController extends Controller {

    /**
     * @Route("/ciyo_test", name="ciyo_test")
     * @Template("LycheeWebsiteBundle:Promotion:ciyo_test.html.twig")
     */
    public function indexAction() {
        return array(

        );
    }

} 