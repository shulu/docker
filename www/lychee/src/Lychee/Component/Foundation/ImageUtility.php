<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 6/19/15
 * Time: 4:03 PM
 */

namespace Lychee\Component\Foundation;


use Lychee\Component\Storage\StorageInterface;

class ImageUtility {

    static private function endWith($string, $end) {
        $offset = strrpos($string, $end);
        if ($offset === false) {
            return false;
        } else {
            return strlen($string) - strlen($end) == $offset;
        }
    }

    /**
     * 根据域名不同而使用相应的图片缩放接口
     *
     * @param $imageUrl
     * @param $maxWidth
     * @param $maxHeight
     * @return string
     */
    static public function resize($imageUrl, $maxWidth, $maxHeight) {
        $domain = parse_url($imageUrl, PHP_URL_HOST);
        if ($domain == null) {
            return $imageUrl;
        } else if (self::endWith($domain, 'qiniudn.com')
            || self::endWith($domain, 'dl.ciyo.cn')
            || self::endWith($domain, 'qn.ciyo.cn')
            || self::endWith($domain, 'qn.ciyocon.com')
            || self::endWith($domain, 'dl.ciyocon.com')
        ) {
            if ($maxHeight == 0) {
                $newImgUrl = $imageUrl . '?imageView2/2/w/' . $maxWidth;
            } else if ($maxWidth == 0) {
                $newImgUrl = $imageUrl . '?imageView2/2/h/' . $maxHeight;
            } else {
                $newImgUrl = $imageUrl . '?imageView2/2/w/' . $maxWidth . '/h/' . $maxHeight;
            }
            
            return $newImgUrl;
        } else if (self::endWith($domain, 'img.ciyocon.com') || self::endWith($domain, 'img.ciyo.cn')) {
            if ($maxHeight == 0) {
                return $imageUrl . '/thumbnail/' . $maxWidth;
            } else if ($maxWidth == 0) {
                return $imageUrl . '/thumbnail/x' . $maxHeight;
            } else {
                if ($maxHeight >= 9999) {
                    return $imageUrl . '/thumbnail/' . $maxWidth;
                }
                return $imageUrl . '/thumbnail/' . $maxWidth . 'x' . $maxHeight . '_center';
            }
        } else {
            return $imageUrl;
        }
    }

	/**
	 * @param StorageInterface $storage
	 * @param $imgFile
	 * @param null $imgType
	 * @param int $quality
	 *
	 * @return bool
	 */
    static public function compressImg(StorageInterface $storage, $imgFile, $imgType = null, $quality = 80) {
    	if (null === $imgType) {
		    $imgType = exif_imagetype($imgFile);
	    }
	    if ($imgType === IMAGETYPE_JPEG) {
		    $im = imagecreatefromjpeg($imgFile);
	    } elseif ($imgType === IMAGETYPE_PNG) {
		    $im = imagecreatefrompng($imgFile);
	    } else {
		    return false;
	    }
	    $fileName = tempnam(sys_get_temp_dir(), '');
	    imagejpeg($im, $fileName, $quality);
	    $compressImgUrl = $storage->put($fileName);
	    @unlink($fileName);

	    return $compressImgUrl;
    }


    public static function formatUrl($imageUrl) {
        $imageUrl = str_replace('qn.ciyo.cn', 'qn.ciyocon.com', $imageUrl);
        return $imageUrl;
    }

    public static function formatFreezeUrl($url) {
        $r = parse_url($url);
        $url = $r['scheme'].'://a.ciyocon.com'.$r['path'];
        if (!empty($r['query'])) {
            $url .= '?'.$r['query'];
        }
        return $url;
    }
}