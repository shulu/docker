<?php
namespace Lychee\Module\ContentManagement\Entity;

class AppLaunchImage {

    /**
     * @var int
     */
    protected $width;

    /**
     * @var int
     */
    protected $height;

    /**
     * @var string
     */
    protected $url;

    /**
     * @param string $url
     * @param int $width
     * @param int $height
     */
    public function __construct($url, $width, $height) {
        $this->url = $url;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * @return int
     */
    public function getHeight() {
        return $this->height;
    }

    /**
     * @param int $height
     *
     * @return AppLaunchImage
     */
    public function setHeight($height) {
        $this->height = $height;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return AppLaunchImage
     */
    public function setUrl($url) {
        $this->url = $url;
        return $this;
    }

    /**
     * @return int
     */
    public function getWidth() {
        return $this->width;
    }

    /**
     * @param int $width
     *
     * @return AppLaunchImage
     */
    public function setWidth($width) {
        $this->width = $width;
        return $this;
    }

}