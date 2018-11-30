<?php
namespace Lychee\Module\Authentication\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="auth_client", uniqueConstraints={
 *  @ORM\UniqueConstraint(name="key_udx", columns={"key"})
 * })
 */
class AuthClient {

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="bigint")
     */
    public $id;

    /**
     * @var string
     * @ORM\Column(name="name", type="string", length=60)
     */
    public $name;

    /**
     * @var \DateTime
     * @ORM\Column(name="create_time", type="datetime")
     */
    public $createTime;

    /**
     * @var bool
     * @ORM\Column(name="disabled", type="boolean", options={"default":"0"})
     */
    public $disabled = false;

    /**
     * @var string
     * @ORM\Column(name="key", type="string", length=64, options={"collation":"utf8_bin"})
     */
    public $key;

    /**
     * @var string
     * @ORM\Column(name="secret", type="string", length=64, options={"collation":"utf8_bin"})
     */
    public $secret;

    /**
     * @var string
     * @ORM\Column(name="redirect_uri1", type="string", length=2083, nullable=true)
     */
    public $redirectUri1;

    /**
     * @var string
     * @ORM\Column(name="redirect_uri2", type="string", length=2083, nullable=true)
     */
    public $redirectUri2;

    /**
     * @var string
     * @ORM\Column(name="scope", type="string", length=1000, nullable=true)
     */
    public $scope;

}