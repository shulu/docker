<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 6/21/16
 * Time: 3:45 PM
 */

namespace Lychee\Bundle\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity()
 * @ORM\Table(name="admin_customize_content")
 * Class CustomizeContent
 * @package Lychee\Bundle\AdminBundle\Entity
 */
class CustomizeContent {
    
    const TYPE_USER = 'user';
    
    const TYPE_TOPIC = 'topic';
    
    const TYPE_AUDIT_TOPIC = 'audit_topic';
    
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(name="id", type="bigint")
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     */
    public $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="target_id", type="bigint")
     */
    public $targetId;
}