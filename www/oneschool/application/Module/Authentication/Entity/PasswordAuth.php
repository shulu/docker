<?php
namespace Lychee\Module\Authentication\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="auth_password")
 */
class PasswordAuth {

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="bigint")
     * @ORM\Id
     */
    private $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=64)
     */
    private $encodedPassword;

    /**
     * @var string
     *
     * @ORM\Column(name="salt", type="string", length=64)
     */
    private $salt;

    /**
     * @param int $userId
     *
     * @return PasswordAuth
     */
    public function setUserId($userId) {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserId() {
        return $this->userId;
    }

    /**
     * @param string $encodedPassword
     *
     * @return PasswordAuth
     */
    public function setEncodedPassword($encodedPassword) {
        $this->encodedPassword = $encodedPassword;
        return $this;
    }

    /**
     * @return string
     */
    public function getEncodedPassword() {
        return $this->encodedPassword;
    }

    /**
     * @param string $salt
     *
     * @return PasswordAuth
     */
    public function setSalt($salt) {
        $this->salt = $salt;
        return $this;
    }

    /**
     * @return string
     */
    public function getSalt() {
        return $this->salt;
    }
} 