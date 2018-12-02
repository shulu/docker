<?php
namespace Lychee\Module\Report\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="post_reports", schema="ciyo_report", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="post_reporter_udx", columns={"post_id", "reporter_id"})
 * })
 */
class PostReport {

    /**
     * @var int
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var int
     * @ORM\Column(name="reporter_id", type="bigint")
     */
    public $reporterId;

    /**
     * @var int
     * @ORM\Column(name="post_id", type="bigint")
     */
    public $postId;

    /**
     * @var \DateTime
     * @ORM\Column(name="time", type="datetime")
     */
    public $time;
}