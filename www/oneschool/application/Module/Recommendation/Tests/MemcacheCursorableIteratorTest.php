<?php
namespace Lychee\Module\Recommendation\Tests;

use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Foundation\CursorableIterator\ArrayCursorableIterator;
use Lychee\Component\Foundation\CursorableIterator\QueryCursorableIterator;
use Lychee\Module\Recommendation\MemcacheCursorableIterator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Lsw\MemcacheBundle\Cache\MemcacheInterface;

/**
 * @group Recommendation
 */
class MemcacheCursorableIteratorTest extends KernelTestCase {
    use ModuleAwareTrait;

    /**
     * @var ContainerInterface
     */
    protected $container;

    protected function setUp() {
        $kernelClass = self::getKernelClass();
        $kernel = new $kernelClass('dev', true);
        $kernel->boot();
        $this->container = $kernel->getContainer();
    }

    /**
     * @return EntityManager
     */
    private function entityManager() {
        return $this->container->get('doctrine')->getManager();
    }

    /**
     * @return MemcacheInterface
     */
    private function memcache() {
        return $this->container->get('memcache.default');
    }

    public function testCache() {
        $em = $this->entityManager();
        $arrayCI = new ArrayCursorableIterator(str_split('abcdefghijklmnopqrst'));

        $memcacheCI = new MemcacheCursorableIterator($this->memcache(), 'testKey', $arrayCI);
        $memcacheCI->setCursor(0);
        $memcacheCI->setStep(6);

        $this->assertEquals(str_split('abcdef'), $memcacheCI->current());
        $memcacheCI->next();
        $this->assertEquals(str_split('ghijkl'), $memcacheCI->current());
        $memcacheCI->next();
        $this->assertEquals(str_split('mnopqr'), $memcacheCI->current());
        $memcacheCI->next();
        $this->assertEquals(str_split('st'), $memcacheCI->current());

//        var_dump($memcacheCI->current());
//        var_dump($memcacheCI->getNextCursor());
//        var_dump($this->memcache()->get('testKey'));
//        $memcacheCI->next();
//        var_dump($memcacheCI->current());
//        var_dump($memcacheCI->getNextCursor());
//        var_dump($this->memcache()->get('testKey'));
        $this->memcache()->delete('testKey');
    }
}
 