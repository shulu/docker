<?php
namespace Lychee\Bundle\CoreBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;

trait ContainerAwareTrait {
    /**
     * @return ContainerInterface
     */
    protected function container() {
        return isset($this->container) ? $this->container : $this->getContainer();
    }
} 