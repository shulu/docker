<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 9/2/16
 * Time: 4:40 PM
 */

namespace Lychee\Module\Recommendation;


use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Lychee\Component\Storage\StorageException;
use Lychee\Component\Storage\StorageInterface;
use Lychee\Module\Recommendation\Entity\AppChannelPackage;
use Lychee\Module\Recommendation\Entity\AppChannelTitle;
use Symfony\Bridge\Doctrine\RegistryInterface;

class AppChannelManagement {

	/**
	 * @var \Doctrine\Common\Persistence\ObjectManager
	 */
	private $em;

	/**
	 * @var StorageInterface
	 */
	private $storage;

    public function __construct(RegistryInterface $doctrineRegistry, $entityManagerName, StorageInterface $storage) {
	    $this->em = $doctrineRegistry->getManager($entityManagerName);
	    $this->storage = $storage;
    }

    public function listAllTitles() {
    	return $this->em->getRepository(AppChannelTitle::class)->findAll();
    }

	/**
	 * @param $id
	 *
	 * @return null|AppChannelTitle
	 */
    public function fetchOneTitle($id) {
    	return $this->em->getRepository(AppChannelTitle::class)->find($id);
    }

	/**
	 * @param $code
	 * @param $title
	 * @throws UniqueConstraintViolationException
	 */
    public function createTitle($code, $title) {
    	$appChannelTitle = new AppChannelTitle();
	    $appChannelTitle->code = $code;
	    $appChannelTitle->title = $title;
	    $this->em->persist($appChannelTitle);
	    $this->em->flush();
    }

    public function removeTitle($id) {
    	$appChannelTitle = $this->em->getRepository(AppChannelTitle::class)->find($id);
	    if ($appChannelTitle) {
	    	$this->em->remove($appChannelTitle);
		    $this->em->flush();
	    }
    }

    public function updateTitle($id, $code, $title) {
    	$appChannelTitle = $this->fetchOneTitle($id);
	    if ($appChannelTitle) {
	    	$appChannelTitle->code = $code;
		    $appChannelTitle->title = $title;
    	    $this->em->flush();
	    }
    }

    public function listAllPackages() {
    	return $this->em->getRepository(AppChannelPackage::class)->findAll();
    }

    public function createPackage($code, $packageUrl) {
    	$package = $this->em->getRepository(AppChannelPackage::class)->findOneBy([
    		'code' => $code
	    ]);
	    if (!$package) {
	    	$package = new AppChannelPackage();
	    } else {
		    $link = $package->link;
		    $samePackages = $this->em->getRepository(AppChannelPackage::class)->findBy([
			    'link' => $link,
		    ]);
		    if (count($samePackages) <= 1) {
			    try {
				    $this->storage->delete($link);
			    } catch (StorageException $e) {

			    }
		    }
	    }
	    $package->code = $code;
	    $package->link = $packageUrl;
	    $package->updateTime = new \DateTime();
	    $this->em->persist($package);
	    $this->em->flush();
    }

    public function deletePackage($id) {
    	/** @var AppChannelPackage $package */
    	$package = $this->em->getRepository(AppChannelPackage::class)->find($id);
	    if ($package) {
		    $link = $package->link;
		    $this->em->remove($package);
		    $this->em->flush();

		    $samePackage = $this->em->getRepository(AppChannelPackage::class)->findOneBy([
		    	'link' => $link,
		    ]);
		    if (!$samePackage) {
			    try {
				    $this->storage->delete($link);
			    } catch (StorageException $e) {

			    }
		    }
	    }
    }

	/**
	 * 根据key(又称code)获取标题
	 * @param $key
	 *
	 * @return object
	 */
    public function getTitleByKey($key) {
    	return $this->em->getRepository(AppChannelTitle::class)->findOneBy([
    		'code' => $key
	    ]);
    }

	/**
	 * 根据key(又称code)获取下载链接
	 * @param $key
	 *
	 * @return object
	 */
    public function getLinkByKey($key) {
    	return $this->em->getRepository(AppChannelPackage::class)->findOneBy([
    		'code' => $key
	    ]);
    }
}