<?php
namespace Lychee\Module\Report\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="comment_reports", schema="ciyo_report", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="comment_reporter_udx", columns={"comment_id", "reporter_id"})
 * })
 */
class CommentReport {

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
     * @ORM\Column(name="comment_id", type="bigint")
     */
    public $commentId;

    /**
     * @var \DateTime
     * @ORM\Column(name="time", type="datetime")
     */
    public $time;
}