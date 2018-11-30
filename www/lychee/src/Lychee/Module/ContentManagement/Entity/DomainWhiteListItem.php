<?php
namespace Lychee\Module\ContentManagement\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="domain_whitelist", uniqueConstraints={
 *   @ORM\UniqueConstraint(name="domain_udx", columns={"domain"})
 * });
 */
class DomainWhiteListItem {

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO");
     * @ORM\Column(name="id", type="integer");
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=20)
     */
    public $name;

    /**
     * @var string
     *
     * @ORM\Column(name="domain", type="string", length=50)
     */
    public $domain;

    /**
     * @var string DomainType
     *
     * @ORM\Column(name="type", type="smallint")
     */
    public $type;
}