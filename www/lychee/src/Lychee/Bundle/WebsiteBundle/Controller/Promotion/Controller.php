<?php
namespace Lychee\Bundle\WebsiteBundle\Controller\Promotion;

use Lychee\Bundle\CoreBundle\Controller\Controller as BaseController;
use Lychee\Bundle\ApiBundle\DataSynthesizer\SynthesizerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Controller extends BaseController {

    public function setContainer(ContainerInterface $container = null) {
        parent::setContainer($container);
        if ($container->has('profiler')) {
            $container->get('profiler')->disable();
        }
    }


    /**
     * @return SynthesizerBuilder
     */
    protected function synthesizerBuilder() {
        return $this->container->get('lychee_api.synthesizer_builder');
    }
} 