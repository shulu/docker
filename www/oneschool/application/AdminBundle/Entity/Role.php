<?php
namespace Lychee\Bundle\AdminBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Lychee\Bundle\AdminBundle\Entity\Manager;

/**
 *
 * @ORM\Entity()
 * @ORM\Table(name="admin_role")
 */
class Role implements RoleInterface {
    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="integer")
     *
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50)
     */
    public $name;

    /**
     * @var string
     *
     * @ORM\Column(name="role", type="string", length=50, unique=true)
     */
    public $role;

    /**
     * @var string
     *
     * @ORM\Column(name="route_name", type="string", length=100)
     */
    public $routeName;

    /**
     * @var array
     *
     * @ORM\ManyToMany(targetEntity="Manager", mappedBy="roles")
     */
    public $managers;

    public function __construct() {
        $this->managers = new ArrayCollection();
    }

    /**
     * @see RoleInterface
     */
    public function getRole()
    {
        return $this->role;
    }
} 