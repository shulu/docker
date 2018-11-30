<?php
namespace Lychee\Bundle\WebsiteBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Lychee\Bundle\WebsiteBundle\Wechat\JsSDK;

class Extension extends \Twig_Extension {
    use ContainerAwareTrait;

    private $iconUrl;

    public function initRuntime(\Twig_Environment $environment) {
//        $asset = $environment->getFunction('asset')->getCallable();
//        $this->iconUrl = $asset('bundles/lycheewebsite/common/image/app_icon.png');
    }

    public function getGlobals() {
        return array(
            'ciyo' => array(
                'app_name' => '次元社',
                'app_slogan' => '二次元短视频社区',
                'app_icon' => 'http://qn.ciyocon.com/app/icons/v2/2_5.png',
            )
        );
    }

    public function getFunctions() {
        return array(
            new \Twig_SimpleFunction('signWechat', array($this, 'signWechat')),
        );
    }

    public function signWechat() {
        /** @var JsSDK $jsSDK */
        $jsSDK = $this->container->get('wechat.jssdk');
        return $jsSDK->getSignPackage();
    }


    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName() {
        return 'lychee.website.extension';
    }

}
