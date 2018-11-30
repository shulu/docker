<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 15-2-3
 * Time: 下午3:36
 */

namespace Lychee\Module\ContentManagement\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class InputDomainRecord
 * @package Lychee\Module\ContentManagement\Entity
 * @ORM\Entity()
 * @ORM\Table(name="input_domain_record")
 */
class InputDomainRecord {
    /**
     * @var
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @var
     *
     * @ORM\Column(type="datetime")
     */
    public $datetime;

    /**
     * @var
     *
     * @ORM\Column(name="user_id", type="bigint")
     */
    public $userId;

    /**
     * @var
     *
     * @ORM\Column(name="domain_id", type="integer")
     */
    public $domainId;
}