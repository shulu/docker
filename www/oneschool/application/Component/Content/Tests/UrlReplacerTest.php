<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 3/12/15
 * Time: 3:50 PM
 */

namespace Lychee\Component\Content\Tests;


use Lychee\Component\Test\ModuleAwareTestCase;

/**
 * @coversDefaultClass \Lychee\Component\Content\UrlReplacer
 */
class UrlReplacerTest extends ModuleAwareTestCase {


    public function getUrlReplacer()
    {
        return  $this->container()->get('lychee.component.url_replacer');
    }

    public function testReplaceAll()
    {
        $replacer = $this->getUrlReplacer();

        $orgUrl = 'http://baidu.com?a=1&b=a&c=%2%';
        $tpl = "%s aaaa %s... %s 
        %s";
        $content = str_replace('%s', $orgUrl, $tpl);
        $r = $replacer->all($content);
        $content = str_replace('%s', $replacer->getTargetUrl(), $tpl);
        $this->assertEquals($content, $r);

        $tpl = "%s aaaa aaa%s aaaa... %s 
        %s";
        $content = str_replace('%s', $orgUrl, $tpl);
        $r = $replacer->all($content);
        $content = str_replace('%s', $replacer->getTargetUrl(), $tpl);
        $this->assertEquals($content, $r);

        $orgUrl = 'https://pan.baidu.com/mbox/homepage?short=hskp1Ve';
        $tpl = "%s你懂的";
        $content = str_replace('%s', $orgUrl, $tpl);
        $r = $replacer->all($content);
        $content = str_replace('%s', $replacer->getTargetUrl(), $tpl);
        $this->assertEquals($content, $r);

    }

}