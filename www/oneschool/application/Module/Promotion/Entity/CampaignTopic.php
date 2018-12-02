<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 8/22/16
 * Time: 6:47 PM
 */

namespace Lychee\Module\Promotion\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class CampaignTopic
 * @package Lychee\Module\Promotion\Entity
 * @ORM\Entity()
 * @ORM\Table(name="campaign_topic")
 */
class CampaignTopic {

    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="bigint", name="campaign_id")
     */
    public $campaignId;

    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="bigint", name="topic_id")
     */
    public $topicId;

    /**
     * @var integer
     * @ORM\Column(type="smallint", name="position")
     */
    public $position;
}