<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 10/12/15
 * Time: 12:28 PM
 */

namespace Lychee\Module\Analysis\Entity;


use Doctrine\ORM\Mapping as ORM;

/**
 * Class TopicsViews
 * @package Lychee\Module\Analysis\Entity
 * @ORM\Entity()
 * @ORM\Table(
 *     name="admin_topics_views",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="topic_date_idx",columns={"topic_id","date"})},
 *     indexes={@ORM\Index(name="date_idx",columns={"date"})}
 * )
 */
class TopicsViews {

    /**
     * @var
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(type="bigint")
     */
    public $id;

    /**
     * @var
     *
     * @ORM\Column(name="topic_id", type="bigint")
     */
    public $topicId;

    /**
     * @var
     *
     * @ORM\Column(type="date")
     */
    public $date;

    /**
     * @var
     *
     * @ORM\Column(name="uni_views", type="integer")
     */
    public $uniViews;

    /**
     * @var
     *
     * @ORM\Column(name="views", type="integer")
     */
    public $views;
}