<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 8/2/16
 * Time: 6:29 PM
 */

namespace Lychee\Module\Recommendation\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="captured_site_content")
 */

class CapturedSiteContent {

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="site_name", type="string", length=20)
     */
    private $siteName;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="content_id", type="bigint")
     */
    private $contentId;

    /**
     * @return string
     */
    public function getSiteName()
    {
        return $this->siteName;
    }

    /**
     * @param string $siteName
     */
    public function setSiteName($siteName)
    {
        $this->siteName = $siteName;
    }

    /**
     * @return int
     */
    public function getContentId()
    {
        return $this->contentId;
    }

    /**
     * @param int $contentId
     */
    public function setContentId($contentId)
    {
        $this->contentId = $contentId;
    }

}