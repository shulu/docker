<?php
namespace Lychee\Module\Relation\Entity;

use Lychee\Component\GraphStorage\Doctrine\AbstractFollowing;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_following", uniqueConstraints={
 *   @ORM\UniqueConstraint(
 *     name="follower_followee_state_udx",
 *     columns={"follower_id", "followee_id", "state"}
 *   ),
 *   @ORM\UniqueConstraint(
 *     name="follower_state_id_udx",
 *     columns={"follower_id", "state", "id"}
 *   )
 * }, indexes={
 *   @ORM\Index(name="update_time_idx", columns={"update_time"})
 * })
 */
class UserFollowing extends AbstractFollowing {

} 