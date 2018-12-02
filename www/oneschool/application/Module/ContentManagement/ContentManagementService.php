<?php
namespace Lychee\Module\ContentManagement;

use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Lychee\Component\KVStorage\CachedDoctrineStorage;
use Lsw\MemcacheBundle\Cache\MemcacheInterface;
use Lychee\Component\KVStorage\MemcacheStorage;
use Lychee\Module\ContentManagement\Entity\AppLaunchImage;
use Lychee\Module\ContentManagement\Entity\Setting;

class ContentManagementService {

    private $entityManager;
    private $memcache;
    private $doctrineStorage;

    /**
     * @param RegistryInterface $doctrineRegistry
     * @param string $entityManagerName
     * @param MemcacheInterface $memcache
     */
    public function __construct($doctrineRegistry, $entityManagerName, $memcache) {
        $this->entityManager = $doctrineRegistry->getManager($entityManagerName);
        $this->memcache = $memcache;
        $memcacheStorage = new MemcacheStorage($this->memcache, 'setting:');
        $this->doctrineStorage = new CachedDoctrineStorage(
            $this->entityManager, Setting::class, $memcacheStorage
        );
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getSetting($key) {
        /** @var Setting $setting */
        $setting = $this->doctrineStorage->get($key);
        if ($setting instanceof Setting == false) {
            return null;
        } else {
            return $setting->getValue();
        }
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function setSetting($key, $value) {
        $setting = new Setting();
        $setting->setKey($key);
        $setting->setValue($value);
        $mergedSetting = $this->entityManager->merge($setting);
        $this->doctrineStorage->set($key, $mergedSetting);
    }

    /**
     * @param AppLaunchImage[] $launchImages
     */
    public function updateAppLaunchImages(array $launchImages) {
        $data = array();
        foreach ($launchImages as $eachImage) {
            $data[] = array(
                'url' => $eachImage->getUrl(),
                'width' => $eachImage->getWidth(),
                'height' => $eachImage->getHeight()
            );
        }

        $json = json_encode($data);
        $this->setSetting('app.launch_images', $json);
    }

    /**
     * @return AppLaunchImage[]
     */
    public function fetchAppLaunchImages() {
        $json = $this->getSetting('app.launch_images');
        if ($json === null) {
            return array();
        }
        $data = json_decode($json, true);
        $launchImages = array_map(function($eachData){
            return new AppLaunchImage($eachData['url'], $eachData['width'], $eachData['height']);
        }, $data);
        return $launchImages;
    }

    /**
     * @return string
     */
    public function androidDownloadLink() {
        return $this->getSetting('app.android_download_link');
    }

    /**
     * @param string $link
     */
    public function updateAndroidDownloadLink($link) {
        $this->setSetting('app.android_download_link', $link);
    }

    /**
     * @param $videoUrl
     * @param bool $getImageSize
     * @return array|bool
     *
     * 参数$getImageSize为真时, 返回数组示例: ['coverUrl' => 'http://xxx', 'width' => 320, 'height' = 240]
     */
    public function fetchVideoCover($videoUrl, $getImageSize = false) {
        $coverUrl = false;
        if (preg_match('/video\.weibo\.com/i', $videoUrl)) {
            $content = file_get_contents($videoUrl);
            if (false !== $content) {
                preg_match('/<img.*?src\s*=\s*"([^"]+)"/im', $content, $matches);
                if ($matches && isset($matches[1])) {
                    $coverUrl = $matches[1];
                }
            }
        } elseif (preg_match('/bilibili\.com\/\S+\/av(\d+)/i', $videoUrl, $matches)) {
            if ($matches && isset($matches[1])) {
	            $content = file_get_contents($videoUrl);
	            preg_match('/<meta itemprop="thumbnailUrl".+?content="(.+?)"/im', $content, $matches);
	            if ($matches && isset($matches[1])) {
		            $coverUrl = $matches[1];
	            }
            }
        } elseif (preg_match('/miaopai\.com\/show\//i', $videoUrl, $matches)) {
            $content = file_get_contents($videoUrl);
            if (false !== $content) {
                preg_match('/video_img.*\s*.*?<img.*?src\s*=\s*"([^"]+)"/im', $content, $matches);
                if ($matches && isset($matches[1])) {
                    $coverUrl = $matches[1];
                }
            }
        }
        if (false !== $getImageSize && $coverUrl ) {
            $imageInfo = getimagesize($coverUrl);
            if (false !== $imageInfo) {
                list($width, $height) = $imageInfo;

                return compact('coverUrl', 'width', 'height');
            }
        }

        return $coverUrl;
    }
}