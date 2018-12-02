<?php
namespace Lychee\Module\Game\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="game",indexes={@ORM\Index(name="category_idx", columns={"category_id"})})
 * @ORM\HasLifecycleCallbacks()
 */
class Game {

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @ORM\Column(name="id", type="bigint")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="app_name", type="string", length=255)
     */
    private $appName;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_time", type="datetime")
     */
    private $createTime;

    /**
     * @var
     *
     * @ORM\Column(name="short_description", type="string", length=255)
     */
    private $shortDescription;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=1000)
     */
    private $description;

    /**
     * @var
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $banner;

    /**
     * @var
     *
     * @ORM\Column(type="string", length=255)
     */
    private $icon;

    /**
     * @var
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var
     *
     * @ORM\Column(name="app_type", type="string", length=255)
     */
    private $appType;

    /**
     * @var
     *
     * @ORM\Column(name="ios_size", type="string", length=10, nullable=true)
     */
    private $iosSize;

    /**
     * @var
     *
     * @ORM\Column(name="android_size", type="string", length=10, nullable=true)
     */
    private $androidSize;

    /**
     * @var
     *
     * @ORM\Column(name="ios_link", type="string", length=255, nullable=true)
     */
    private $iosLink;

    /**
     * @var
     *
     * @ORM\Column(name="android_link", type="string", length=255, nullable=true)
     */
    private $androidLink;

    /**
     * @var
     *
     * @ORM\Column(name="app_screenshots", type="string", length=2083))
     */
    private $appScreenshots;

    /**
     * @var
     *
     * @ORM\Column(name="topic_id", type="bigint", nullable=true)
     */
    private $topicId;

    /**
     * @var
     *
     * @ORM\Column(name="category_id", type="smallint", nullable=true)
     */
    private $categoryId;

    /**
     * @var string
     * @ORM\Column(name="publisher", type="string", length=255, nullable=true))
     */
    private $publisher;
    
    /**
     * @var
     * @ORM\Column(name="player_numbers", type="bigint", nullable=true, options={"default":"0"})
     */
    private $playerNumbers;

    /**
     * @var
     * @ORM\Column(name="launch_date", type="datetime")
     */
    private $launchDate;

    public function __construct() {
        $this->topics = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id) {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getAppName() {
        return $this->appName;
    }

    /**
     * @param string $appName
     * @return $this
     */
    public function setAppName($appName) {
        $this->appName = $appName;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreateTime() {
        return $this->createTime;
    }

    /**
     * @param $createTime
     * @return $this
     */
    public function setCreateTime($createTime) {
        $this->createTime = $createTime;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getShortDescription() {
        return $this->shortDescription;
    }

    /**
     * @param mixed $shortDescription
     * @return $this
     */
    public function setShortDescription($shortDescription) {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description) {
        $this->description = $description;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBanner() {
        return $this->banner;
    }

    /**
     * @param mixed $banner
     * @return $this
     */
    public function setBanner($banner) {
        $this->banner = $banner;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIcon() {
        return $this->icon;
    }

    /**
     * @param mixed $icon
     * @return $this
     */
    public function setIcon($icon) {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param mixed $title
     * @return $this
     */
    public function setTitle($title) {
        $this->title = $title;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAppType() {
        return $this->appType;
    }

    /**
     * @param mixed $appType
     * @return $this
     */
    public function setAppType($appType) {
        $this->appType = $appType;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIosSize() {
        return $this->iosSize;
    }

    /**
     * @param mixed $iosSize
     * @return $this
     */
    public function setIosSize($iosSize) {
        $this->iosSize = $iosSize;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAndroidSize() {
        return $this->androidSize;
    }

    /**
     * @param mixed $androidSize
     * @return $this
     */
    public function setAndroidSize($androidSize) {
        $this->androidSize = $androidSize;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIosLink() {
        return $this->iosLink;
    }

    /**
     * @param mixed $iosLink
     * @return $this
     */
    public function setIosLink($iosLink) {
        $this->iosLink = $iosLink;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAndroidLink() {
        return $this->androidLink;
    }

    /**
     * @param mixed $androidLink
     * @return $this
     */
    public function setAndroidLink($androidLink) {
        $this->androidLink = $androidLink;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAppScreenshots() {
        return $this->appScreenshots;
    }

    /**
     * @param mixed $appScreenshots
     * @return $this
     */
    public function setAppScreenshots($appScreenshots) {
        $this->appScreenshots = $appScreenshots;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTopicId() {
        return $this->topicId;
    }

    /**
     * @param mixed $topicId
     * @return $this
     */
    public function setTopicId($topicId) {
        $this->topicId = $topicId;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreateTimeValue() {
        $this->createTime = new \DateTime();
    }

    /**
     * @return mixed
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param mixed $categoryId
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * @return string
     */
    public function getPublisher()
    {
        return $this->publisher;
    }

    /**
     * @param string $publisher
     */
    public function setPublisher($publisher)
    {
        $this->publisher = $publisher;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPlayerNumbers()
    {
        return $this->playerNumbers;
    }

    /**
     * @param mixed $playerNumbers
     */
    public function setPlayerNumbers($playerNumbers)
    {
        $this->playerNumbers = $playerNumbers;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLaunchDate()
    {
        return $this->launchDate;
    }

    /**
     * @param mixed $launchDate
     */
    public function setLaunchDate($launchDate)
    {
        $this->launchDate = $launchDate;

        return $this;
    }
    
}