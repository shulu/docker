<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 5/26/16
 * Time: 2:56 PM
 */

namespace Lychee\Module\ContentAudit\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class ImageReviewSource
 * @package Lychee\Module\ContentAudit\Entity
 * @ORM\Entity()
 * @ORM\Table(
 *     name="image_review_source",
 *     indexes={@ORM\Index(name="source_idx", columns={"source_type", "source_id"})}
 * )
 */
class ImageReviewSource {

    const TYPE_USER_AVATAR = 1;

    const TYPE_TOPIC_COVER = 2;

    const TYPE_POST = 3;
    
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="review_id", type="bigint")
     */
    public $reviewId;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="source_type", type="smallint")
     */
    public $sourceType;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="source_id", type="bigint")
     */
    public $sourceId;
}