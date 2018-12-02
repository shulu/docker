<?php
namespace Lychee\Bundle\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Lychee\Bundle\AdminBundle\Entity\Role;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Doctrine\Common\Collections\ArrayCollection;

/**
 *
 * @ORM\Entity()
 * @ORM\Table(name="admin_manager")
 */
class Manager implements AdvancedUserInterface, \Serializable {
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, unique=true)
     */
    public $email;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=20)
     */
    public $name;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=64)
     */
    public $password;

    /**
     * @var string
     *
     * @ORM\Column(name="salt", type="string", length=64)
     */
    public $salt;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Role", inversedBy="managers")
     * @ORM\JoinTable(name="admin_manager_role",
     *   joinColumns={
     *     @ORM\JoinColumn(name="manager_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="role_id", referencedColumnName="id")
     *   }
     * )
     */
    public $roles;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_time", type="datetime")
     */
    public $createTime;

    /**
     * @var boolean
     *
     * @ORM\Column(name="frozen", type="boolean")
     */
    public $frozen = false;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="OperationAccount", inversedBy="managers")
     * @ORM\JoinTable(name="admin_manager_operation_account",
     *   joinColumns={
     *     @ORM\JoinColumn(name="manager_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="operation_account_id", referencedColumnName="id")
     *   }
     * )
     */
    public $operationAccounts;

    public function __construct() {
        $this->roles = new ArrayCollection();
    }

    /**
     * Checks whether the user's account has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw an AccountExpiredException and prevent login.
     *
     * @return bool    true if the user's account is non expired, false otherwise
     *
     * @see AccountExpiredException
     */
    public function isAccountNonExpired() {
        return true;
    }

    /**
     * Checks whether the user is locked.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a LockedException and prevent login.
     *
     * @return bool    true if the user is not locked, false otherwise
     *
     * @see LockedException
     */
    public function isAccountNonLocked() {
        return true;
    }

    /**
     * Checks whether the user's credentials (password) has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a CredentialsExpiredException and prevent login.
     *
     * @return bool    true if the user's credentials are non expired, false otherwise
     *
     * @see CredentialsExpiredException
     */
    public function isCredentialsNonExpired() {
        return true;
    }

    /**
     * Checks whether the user is enabled.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a DisabledException and prevent login.
     *
     * @return bool    true if the user is enabled, false otherwise
     *
     * @see DisabledException
     */
    public function isEnabled() {
        return $this->frozen === false;
    }

    public function serialize() {
//        return serialize(array(
//            $this->id, $this->email, $this->name,
//            $this->password, $this->salt, $this->roles->toArray(),
//            $this->createTime, $this->frozen
//        ));
        return serialize($this->id);
    }

    public function unserialize($serialized) {
//        list(
//            $this->id, $this->email, $this->name,
//            $this->password, $this->salt, $roles,
//            $this->createTime, $this->frozen
//        ) = unserialize($serialized);
//
//        $this->roles = new ArrayCollection($roles);
        $this->id = unserialize($serialized);
    }

    /**
     * Returns the roles granted to the user.
     *
     * <code>
     * public function getRoles()
     * {
     *     return array('ROLE_USER');
     * }
     * </code>
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return \Symfony\Component\Security\Core\Role\Role[] The user roles
     */
    public function getRoles() {
        return $this->roles->toArray();
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string The password
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt() {
        return $this->salt;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername() {
        return $this->email;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials() {
        //do nothing
    }

} 