<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 5/26/16
 * Time: 12:18 PM
 */

namespace Lychee\Module\ContentAudit\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class ImageReview
 * @package Lychee\Module\ContentAudit\Entity
 * @ORM\Entity()
 * @ORM\Table(
 *     name="image_review",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="image_idx", columns={"image"})},
 *     indexes={@ORM\Index(name="last_review_time_idx", columns={"last_review_time"})}
 * )
 */
class ImageReview {

    const RESULT_PASS = 0;

    const RESULT_REJECT = 1;

    const LABEL_LEGAL = 2;

    const LABEL_SEXY = 1;

    const LABEL_PORN = 0;

    const SOURCE_MANUAL = 1;

    const SOURCE_AUTO = 2;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(type="bigint")
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=255)
     */
    public $image;

    /**
     * @var integer
     *
     * @ORM\Column(name="label", type="smallint")
     */
    public $label;

    /**
     * @var boolean
     *
     * @ORM\Column(name="review", type="boolean")
     */
    public $review;

    /**
     * @var float
     *
     * @ORM\Column(name="rate", type="float")
     */
    public $rate = 0.0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_review_time", type="datetime")
     */
    public $lastReviewTime;

    /**
     * @var int
     *
     * @ORM\Column(name="review_result", type="smallint")
     */
    public $reviewResult = self::RESULT_PASS;

    /**
     * @var int
     *
     * @ORM\Column(name="review_source", type="smallint", options={"default":"1", "comment":"审核处理来源"})
     */
    public $reviewSource = self::SOURCE_MANUAL;


    public static function getReviewSourceMapping() {
        $ret =[];
        $ret[self::SOURCE_MANUAL] = '手动';
        $ret[self::SOURCE_AUTO] = '自动';
        return $ret;
    }
}