<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 10/28/15
 * Time: 11:35 AM
 */

namespace Lychee\Module\ContentManagement\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class ExpressionPackageVersion
 * @package Lychee\Module\ContentManagement\Entity
 * @ORM\Entity()
 * @ORM\Table(name="expression_package_version")
 */
class ExpressionPackageVersion {

    /**
     * @var
     *
     * @ORM\Id
     * @ORM\Column(name="version", type="integer")
     */
    private $version;

    /**
     * @return mixed
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * @param mixed $version
     */
    public function setVersion($version) {
        $this->version = $version;
    }

}