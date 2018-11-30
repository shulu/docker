<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Module\Recommendation\Entity\Banner;

class BannerSynthesizer extends AbstractSynthesizer {
    /**
     * @param Banner $entity
     * @param mixed $info
     * @return array
     */
    protected function synthesize($entity, $info = null) {
        return array(
            'id' => $entity->id,
            'url' => $entity->url,
            'image_url' => $entity->imageUrl,
            'image_width' => $entity->imageWidth,
            'image_height' => $entity->imageHeight,
            'description' => $entity->description,
            'title' => $entity->title,
            'share_title' => $entity->shareTitle,
            'share_text' => $entity->shareText,
            'share_image_url' => $entity->shareImageUrl,
            'share_sina_image_url' => $entity->shareBigImageUrl,
        );
    }

} 