<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 9/18/15
 * Time: 6:08 PM
 */

namespace Lychee\Module\Post;


/**
 * Class PostAnnotation
 * @package Lychee\Module\Post
 */
class PostAnnotation {

    const ORIGINAL_URL = 'original_url';

    const IMAGE_WIDTH = 'image_width';

    const IMAGE_HEIGHT = 'image_height';

    const MULTI_PHOTOS = 'multi_photos';

    const MULTI_ORIGINAL_PHOTOS = 'multi_original_photos';

    const RESOURCE_LINK = 'resource_link';

    const RESOURCE_TITLE = 'resource_title';

    const RESOURCE_THUMB = 'resource_thumb';

    const RESOURCE_VIDEO_COVER = 'video_cover';

    const RESOURCE_VIDEO_COVER_WIDTH = 'video_cover_width';

    const RESOURCE_VIDEO_COVER_HEIGHT = 'video_cover_height';

    const MULTI_PHOTO_HEIGHTS = 'multi_photo_heights';

    const MULTI_PHOTO_WIDTHS = 'multi_photo_widths';

    const MULTI_PHOTO_GIF_INDEX = 'multi_photo_gif_index';

    /**
     * @param $HDImageUrl
     * @param $imageUrl
     * @param $imageWidth
     * @param $imageHeight
     * @return array
     */
    static public function setSinglePhoto($HDImageUrl, $imageUrl, $imageWidth, $imageHeight, $gifIndex) {
        $ret = [
            self::ORIGINAL_URL => $imageUrl,
            self::IMAGE_WIDTH => $imageWidth[0],
            self::IMAGE_HEIGHT => $imageHeight[0],
            self::MULTI_PHOTOS => [$imageUrl],
            self::MULTI_PHOTO_HEIGHTS => $imageHeight,
            self::MULTI_PHOTO_WIDTHS => $imageWidth
        ];
        if ($HDImageUrl) {
            $ret[self::MULTI_ORIGINAL_PHOTOS] = [$HDImageUrl];
        }
        if ($gifIndex) {
            $ret[self::MULTI_PHOTO_GIF_INDEX] = $gifIndex;
        }
        return $ret;
    }

    /**
     * @param $multiHDImageUrls
     * @param $multiImageUrls
     * @param $imageWidth
     * @param $imageHeight
     * @return array
     */
    static public function setMultiPhotos($multiHDImageUrls, $multiImageUrls, $imageWidth, $imageHeight, $gifIndex) {
        $ret = [
            self::ORIGINAL_URL => count($multiImageUrls) > 0? $multiImageUrls[0] : '',
            self::MULTI_PHOTOS => $multiImageUrls,
            self::IMAGE_WIDTH => $imageWidth[0],
            self::IMAGE_HEIGHT => $imageHeight[0],
            self::MULTI_PHOTO_HEIGHTS => $imageHeight,
            self::MULTI_PHOTO_WIDTHS => $imageWidth
        ];
        if ($multiHDImageUrls) {
            $ret[self::MULTI_ORIGINAL_PHOTOS] = $multiHDImageUrls;
        }
        if ($gifIndex) {
            $ret[self::MULTI_PHOTO_GIF_INDEX] = $gifIndex;
        }
        return $ret;
    }

    /**
     * @param $link
     * @param string $title
     * @param string $thumb
     * @return array
     */
    static public function setResource($link, $title = '', $thumb = '') {
        return [
            self::RESOURCE_LINK => $link,
            self::RESOURCE_TITLE => $title,
            self::RESOURCE_THUMB => $thumb,
        ];
    }

    /**
     * @param $url
     * @return array
     */
    static public function setVideoCover($url) {
        if ($url) {
            list($width, $height) = getimagesize($url);

            return [
                self::RESOURCE_VIDEO_COVER => $url,
                self::RESOURCE_VIDEO_COVER_WIDTH => $width,
                self::RESOURCE_VIDEO_COVER_HEIGHT => $height,
            ];
        } else {
            return [];
        }
    }
}