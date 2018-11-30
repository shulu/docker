<?php
namespace Lychee\Component\Content;

class UrlReplacer {

    /**
     * @var string
     */
    private $targetUrl;

    public function __construct($targetUrl)
    {
        $this->targetUrl = $targetUrl;
    }

    public function getTargetUrl()
    {
        return $this->targetUrl;
    }

    /**
     * 替换所有连接
     * @param $content
     * @return mixed
     */
    public function all($content)
    {
        if (empty(trim($content))) {
            return $content;
        }

        if (false===strpos($content, 'http://')
        &&false===strpos($content, 'https://')) {
            return $content;
        }

        $content = preg_replace('/(https|http):\/\/(\.?[^\x{4e00}-\x{9fa5}\.\s]+)+/u', $this->targetUrl, $content);
        return $content;
    }

}