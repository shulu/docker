<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 3/12/15
 * Time: 3:50 PM
 */

namespace Lychee\Component\Storage\Tests;


use Lychee\Component\Test\ModuleAwareTestCase;
use Lychee\Component\Storage\Resource;

/**
 * Class QiniuStorageTest
 * @package Lychee\Component\Storage\Tests
 */
class QiniuStorageTest extends ModuleAwareTestCase {

    /**
     * @var string
     */
    private $tmpFile;

    private $key = 'dev/temp_file';

    /**
     *
     */
    public function testPut() {
        /**
         * @var \Lychee\Component\Storage\QiniuStorage $storage
         */
        $storage = $this->storage();
        $this->tmpFile = tempnam(sys_get_temp_dir(), '');
        file_put_contents($this->tmpFile, 'Test Benson');

        $url = $storage->put($this->tmpFile, $this->key);
        echo $url;
    }

    /**
     *
     */
    public function testDelete() {
        /**
         * @var \Lychee\Component\Storage\QiniuStorage $storage
         */
        $storage = $this->storage();
        $this->assertTrue($storage->delete($this->key));
    }

    /**
     *
     */
    protected function tearDown() {
        @unlink($this->tmpFile);

        parent::tearDown();
    }
}