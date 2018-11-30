<?php
namespace Lychee\Module\ContentManagement\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="blocking_topic", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="channel_version_idx", columns={"channel", "version"})
 * })
 */
class BlockingTopic {

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO");
     * @ORM\Column(name="id", type="integer")
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(name="channel", type="string", length=100)
     */
    public $channel;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=100)
     */
    public $version;

    /**
     * @var string
     *
     * @ORM\Column(name="topics", type="string", length=1024)
     */
    public $topics;

}