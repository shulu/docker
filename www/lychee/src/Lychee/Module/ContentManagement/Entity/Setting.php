<?php
namespace Lychee\Module\ContentManagement\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="cms_setting")
 */
class Setting {

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="`key`", type="string", length=100)
     */
    protected $key;

    /**
     * @var string
     *
     * @ORM\Column(name="`value`", type="text")
     */
    protected $value;

    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return Setting
     */
    public function setKey($key) {
        $this->key = $key;
        return $this;
    }

    /**
     * @return string
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return Setting
     */
    public function setValue($value) {
        $this->value = $value;
        return $this;
    }
}