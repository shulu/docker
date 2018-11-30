<?php
namespace Lychee\Module\Recommendation\Post;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManager;

class GroupManager {

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * GroupManager constructor.
     * @param RegistryInterface $doctrine
     */
    public function __construct($doctrine) {
        $this->em = $doctrine->getManager();
    }

    public function getGroupIdsToShow() {
        $idsToShow = [
            PredefineGroup::ID_JINGXUAN,
            PredefineGroup::ID_ZIPAI,
            PredefineGroup::ID_JIAOYOU,
            PredefineGroup::ID_RICHANG,
            PredefineGroup::ID_HUIHUA,
            PredefineGroup::ID_COSPLAY,
            PredefineGroup::ID_XINGQU2,
            PredefineGroup::ID_GIF,
            PredefineGroup::ID_BIAOQINGBAO,
            PredefineGroup::ID_BIZHITOUXIANG,
            PredefineGroup::ID_DONGMAN2,
            PredefineGroup::ID_YOUXI2,
        ];
        return $idsToShow;
    }

	/**
	 * @param null $appVersion
	 * @param bool $inReview
	 *
	 * @return array
	 */
    public function getGroupsToShow($appVersion = null, $inReview = false, $client=null) {
    	$idsToShow = $this->getGroupIdsToShow();
    	if ($client && strtolower($client)=='ios') {
            $idsToShow = array_diff($idsToShow, [PredefineGroup::ID_ZIPAI]);
        }
        $groups = array();
        foreach ($idsToShow as $groupId) {
            $g = $this->getGroupById($groupId);
            if ($g) {
                $groups[] = $g;
            }
        }
        return $groups;
    }


    public function getDisplayCustomGroupIds() {
        $idsToShow = [
            PredefineGroup::ID_COSPLAY_TAB,
            PredefineGroup::ID_ZHAI_WU_TAB,
            PredefineGroup::ID_ZHAI_XIANG_TAB,
            PredefineGroup::ID_ZIPAI_TAB,
            PredefineGroup::ID_YOUXI2,
            PredefineGroup::ID_DACHU_TAB,
            PredefineGroup::ID_BEAUTIFUL_PIC_TAB,
            PredefineGroup::ID_GIF,
            PredefineGroup::ID_3CY_TAB,
        ];
        return $idsToShow;
    }

    /**
     * @param null $appVersion
     * @param bool $inReview
     *
     * @return array
     */
    public function getDisplayCustomGroups($appVersion = null,  $client=null) {
        $idsToShow = $this->getDisplayCustomGroupIds();
        if ($client && strtolower($client)=='ios') {
            $idsToShow = array_diff($idsToShow, [PredefineGroup::ID_ZIPAI]);
        }
        $groups = array();
        foreach ($idsToShow as $groupId) {
            $g = $this->getGroupById($groupId);
            if ($g) {
                $groups[] = $g;
            }
        }
        return $groups;
    }

    public function setGroupIdsToShow($groupIds) {

    }

    /**
     * @param int $gid
     * @return Group|null
     */
    public function getGroupById($gid) {
        /** @var Group[] $groups */
        $groups = $this->getAllGroups();
        foreach ($groups as $g) {
            if ($g->id() == $gid) {
                return $g;
            }
        }
        return null;
    }

    /**
     * @param string $name
     * @return Group|null
     */
    public function getGroupByName($name) {
        $groups = $this->getAllGroups();
        foreach ($groups as $g) {
            if ($g->name() == $name) {
                return $g;
            }
        }
        return null;
    }

    /**
     * @return Group[]
     */
    public function getAllGroups() {
        return array_merge(PredefineGroup::groups(), $this->getAllTopicsGroups());
    }

    private $topicsGroups = null;

    public function createTopicsGroup($name, $topicIds) {

    }

    public function deleteTopicsGroup($gid) {

    }

    public function updateTopicsGroup($gid, $name, $topicIds) {

    }

    /**
     * @return TopicsGroup[]
     */
    public function getAllTopicsGroups() {
        if ($this->topicsGroups === null) {
            $this->topicsGroups = array(

            );
        }
        return $this->topicsGroups;
    }

    /**
     * 获取与指定分组关联的子分组
     *
     * @param $groupId
     * @return array|mixed
     */
    static public function getSubGroupIds($groupId) {
        $config = [];
        $config[PredefineGroup::ID_JINGXUAN] = [PredefineGroup::ID_COSPLAY_TOPIC, PredefineGroup::ID_ZHAIWU];
        if (isset($config[$groupId])) {
            return $config[$groupId];
        }
        return [];
    }

}