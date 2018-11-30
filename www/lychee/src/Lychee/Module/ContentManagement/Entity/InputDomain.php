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
 * Class InputDomain
 * @package Lychee\Module\ContentManagement\Entity
 * @ORM\Entity()
 * @ORM\Table(name="input_domain", uniqueConstraints={@ORM\UniqueConstraint(name="domain_name_idx", columns={"name"})})
 */
class InputDomain {
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
     * @ORM\Column(type="string", length=255)
     */
    public $name;

    /**
     * @var
     *
     * @ORM\Column(type="integer", options={"default": 0})
     */
    public $count = 0;
}