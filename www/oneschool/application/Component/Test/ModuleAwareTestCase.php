<?php
namespace Lychee\Component\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;

class ModuleAwareTestCase extends KernelTestCase {
    use ModuleAwareTrait;

    /**
     * @var ContainerInterface
     */
    protected $container;

    protected function setUp() {
        parent::setUp();
        $kernelClass = self::getKernelClass();
        $kernel = new $kernelClass('dev', true);
        $kernel->boot();
        $this->container = $kernel->getContainer();
    }
} 