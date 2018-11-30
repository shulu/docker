<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 5/23/16
 * Time: 12:00 PM
 */

namespace Lychee\Module\Recommendation\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class EditorChoiceTopic
 * @package Lychee\Module\Recommendation\Entity
 * @ORM\Entity()
 * @ORM\Table(name="editor_choice_topic")
 */
class EditorChoiceTopic {

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
     * @ORM\Column(name="topic_id", type="bigint")
     */
    public $topicId;

    /**
     * @var
     * 
     * @ORM\Column(name="position", type="integer")
     */
    public $position;

}