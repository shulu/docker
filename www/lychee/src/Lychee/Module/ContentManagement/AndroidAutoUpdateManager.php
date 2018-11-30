<?php
/**
 * Created by PhpStorm.
 * User: ys160726
 * Date: 2016/12/26
 * Time: 下午5:28
 */

namespace Lychee\Module\ContentManagement;


use Lychee\Module\ContentManagement\Entity\AndroidAutoUpdate;
use Symfony\Bridge\Doctrine\RegistryInterface;

class AndroidAutoUpdateManager
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * AndroidAutoUpdateManager constructor.
     * @param RegistryInterface $doctrineRegistry
     */
    public function __construct(RegistryInterface $doctrineRegistry)
    {
        $this->entityManager = $doctrineRegistry->getManager();
    }

    /**
     * @return array
     */
    public function getAllUpdate() {
        $updateList = $this->entityManager->getRepository(AndroidAutoUpdate::class)->findAll();
        if (!$updateList) {
            return array();
        }
        return $updateList;
    }

    /**
     * @param $id
     * @param $version
     * @param $log
     * @param $url
     * @param $size
     * @param \DateTime $uploadDate
     */
    public function editUpdate($id, $version, $log, $url, $size, \DateTime $uploadDate,$versionCode) {
        /** @var AndroidAutoUpdate $update */
        $update = $this->entityManager->getRepository(AndroidAutoUpdate::class)->find($id);
        if($update) {
            $update->version = $version;
            $update->log = $log;
            $update->link = $url;
            $update->size = $size;
            $update->uploadDate = $uploadDate;
            $update->versionCode = $versionCode;
            $this->entityManager->flush();
        }
    }

    /**
     * @param $id
     * @param $state
     */
    public function editState($id, $state) {
        /** @var AndroidAutoUpdate $update */
        $update = $this->entityManager->getRepository(AndroidAutoUpdate::class)->find($id);
        if ($update) {
            $update->autoUpdate = $state;
            $this->entityManager->flush();
        }
    }

	/**
	 * @return null|object
	 */
	public function getLatestUpdate() {
    	$info = $this->entityManager->getRepository(AndroidAutoUpdate::class)->findOneBy([], [
    		'id' => 'DESC'
	    ]);
	    if ($info) {
	    	return $info;
	    }

	    return null;
    }
}