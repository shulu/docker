<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 5/23/16
 * Time: 12:33 PM
 */

namespace Lychee\Module\Recommendation\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class EditorChoiceTopicCategory
 * @package Lychee\Module\Recommendation\Entity
 * @ORM\Entity()
 * @ORM\Table(name="editor_choice_topic_category")
 */
class EditorChoiceTopicCategory {

    /**
     * @var
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @var
     *
     * @ORM\Column(name="category_id", type="integer")
     */
    public $categoryId;

    /**
     * @var
     *
     * @ORM\Column(name="position", type="integer")
     */
    public $position;
}