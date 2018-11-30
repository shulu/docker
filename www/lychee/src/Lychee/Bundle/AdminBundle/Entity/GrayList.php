<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 6/15/16
 * Time: 12:20 PM
 */

namespace Lychee\Bundle\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Entity()
 * @ORM\Table(
 *     name="admin_gray_list",
 *     indexes={
 *         @ORM\Index(name="creator_idx", columns={"creator_id"}),
 *         @ORM\Index(name="topic_idx", columns={"topic_id"}),
 *         @ORM\Index(name="operating_time_idx", columns={"operating_time"})
 *     }
 * )
 */
class GrayList {

    const TABLE_NAME = 'admin_gray_list';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="post_id", type="bigint")
     */
    public $postId;

    /**
     * @var int
     *
     * @ORM\Column(name="creator_id", type="bigint")
     */
    public $creatorId;

    /**
     * @var int
     *
     * @ORM\Column(name="topic_id", type="bigint")
     */
    public $topicId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="operating_time", type="datetime")
     */
    public $operatingTime;

    /**
     * @var int
     *
     * @ORM\Column(name="manager_id", type="integer")
     */
    public $managerId;
}