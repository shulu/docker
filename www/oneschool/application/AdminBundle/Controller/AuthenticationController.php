<?php
namespace Lychee\Bundle\AdminBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 *
 */
class AuthenticationController {
    /**
     * @Route("/signin")
     * @Template()
     */
    public function signinAction() {
        return array();
    }
} 