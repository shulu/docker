<?php
namespace Lychee\Module\Relation\Entity;

use Lychee\Component\GraphStorage\Doctrine\AbstractMetadata;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="user_following_counting")
 */
class UserFollowingCounting extends AbstractMetadata {

}