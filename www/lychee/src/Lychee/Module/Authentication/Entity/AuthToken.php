<?php
namespace Lychee\Module\Authentication\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="auth_token", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="access_token_udx", columns={"access_token"})
 * }, indexes={
 *   @ORM\Index(name="user_id_idx", columns={"user_id"})
 * })
 */
class AuthToken {

	const CLIENT_CIYO = 1;

	const CLIENT_CIYO_WEB = 2;

	const CLIENT_EXTRA_MESSAGE = 3;

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="bigint")
     */
    public $id;

    /**
     * @var string
     * @ORM\Column(name="access_token", type="string", length=64, options={"collation":"utf8_bin"})
     */
    public $accessToken;

    /**
     * @var int
     * @ORM\Column(name="user_id", type="bigint")
     */
    public $userId;

    /**
     * @var int
     * @ORM\Column(name="client_id", type="integer")
     */
    public $clientId;

    /**
     * @var int
     * @ORM\Column(name="scope", type="string", length=1000, nullable=true)
     */
    public $scope;

    /**
     * @var string
     * @ORM\Column(name="grant_type", type="string", length=40)
     */
    public $grantType;

    /**
     * @var int
     * @ORM\Column(name="create_time", type="integer")
     */
    public $createTime;

    /**
     * @var int
     * @ORM\Column(name="ttl", type="integer")
     */
    public $ttl;

}