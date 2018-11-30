<?php
namespace Lychee\Module\Upload\ImageFilter;

use stojg\crop\CropEntropy;

class ResizeFilter implements ImageFilter {
    /**
     * @param string $path
     * @param string $paramString
     *
     * @return \Imagick|false
     */
    public function apply($path, $paramString) {
        if (strlen($paramString) == 0 ||
            preg_match('/^(\d+)?(?:x(\d+)?)?(?:_(left|right|center|auto))?(?:_(top|bottom|center))?$/', $paramString, $matches) == 0) {
            return false;
        }

        $width = isset($matches[1]) ? intval($matches[1]) : 0;
        $height = isset($matches[2]) ? intval($matches[2]) : 0;
        $isFill = isset($matches[3]) ? true : false;
        $xGravity = isset($matches[3]) ? $matches[3] : 'center';
        $yGravity = isset($matches[4]) ? $matches[4] : 'center';
        if ($width === 0 && $height === 0) {
            return false;
        }

        try {
            $im = new \Imagick($path);
            if ($isFill) {
                if ($xGravity == 'auto') {
                    return $this->autoCropAndThumbnailImage($im, $width, $height);
                } else {
                    return $this->cropAndThumbnailImage($im, $width, $height, $xGravity, $yGravity);
                }
            } else {
                return $this->thumbnailImage($im, $width, $height);
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param \Imagick $im
     * @param int $width
     * @param int $height
     *
     * @return \Imagick|bool
     */
    private function thumbnailImage($im, $width, $height) {
        $imageWidth = $im->getImageWidth();
        $imageHeight = $im->getImageHeight();

        if ($imageWidth == 0 || $imageHeight == 0) {
            return false;
        }

        $scale = min($width / $imageWidth, $height / $imageHeight);
        $targetWidth = round($imageWidth * $scale);
        $targetHeight = round($imageHeight * $scale);

        if ($targetWidth >= $imageWidth && $targetHeight >= $imageHeight) {
            return false;
        }
        $targetWidth = $targetWidth ?: $imageWidth;
        $targetHeight = $targetHeight ?: $imageHeight;

        return $im->thumbnailImage($targetWidth, $targetHeight, true, false) ? $im : false;
    }

    /**
     * @param \Imagick $im
     * @param int $width
     * @param int $height
     * @param string $xGravity
     * @param string $yGravity
     *
     * @return \Imagick|bool
     */
    private function cropAndThumbnailImage($im, $width, $height, $xGravity, $yGravity) {
        $imageWidth = $im->getImageWidth();
        $imageHeight = $im->getImageHeight();
        $width = $width ?: $imageWidth;
        $height = $height ?: $imageHeight;

        $scale = min($imageWidth / $width, $imageHeight / $height);
        $cropWidth = round($width * $scale);
        $cropHeight = round($height * $scale);

        $diffWidth = $imageWidth - $cropWidth;
        $diffHeight = $imageHeight - $cropHeight;

        $cropX = $cropY = 0;
        switch ($xGravity) {
            case 'center': $cropX = $diffWidth / 2; break;
            case 'right': $cropX = $diffWidth; break;
            case 'left':
            default: // do nothing
        }
        switch ($yGravity) {
            case 'center': $cropY = $diffHeight / 2; break;
            case 'bottom': $cropY = $diffHeight; break;
            case 'top':
            default: // do nothing
        }
        $ok = $im->cropImage($cropWidth, $cropHeight, $cropX, $cropY);
        if ($ok === false) {
            return false;
        }
        if ($width > $imageWidth || $height > $imageHeight) {
            $width = round($width * $scale);
            $height = round($height * $scale);
        }
        return $im->thumbnailImage($width, $height) ? $im : false;
    }

    /**
     * @param \Imagick $im
     * @param int $width
     * @param int $height
     *
     * @return bool|\Imagick
     */
    private function autoCropAndThumbnailImage($im, $width, $height) {
        $imageWidth = $im->getImageWidth();
        $imageHeight = $im->getImageHeight();

        if ($width > $imageWidth || $height > $imageHeight) {
            $scale = min($imageWidth / $width, $imageHeight / $height);
            $width = round($width * $scale);
            $height = round($height * $scale);
        }
        $width = $width ?: $imageWidth;
        $height = $height ?: $imageHeight;

        $cropper = new CropEntropy();
        $cropper->setImage($im);
        return $cropper->resizeAndCrop($width, $height);
    }
}