<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 15-3-10
 * Time: 下午6:38
 */

namespace Lychee\Module\ContentManagement;


use Lychee\Module\ContentManagement\Entity\Sticker;
use Lychee\Module\ContentManagement\Entity\StickerVersion;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class StickerManagement
 * @package Lychee\Module\ContentManagement
 */
class StickerManagement {

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @param \Symfony\Bridge\Doctrine\RegistryInterface $doctrineRegistry
     * @param $entityManagerName
     */
    public function __construct(RegistryInterface $doctrineRegistry, $entityManagerName) {
        $this->entityManager = $doctrineRegistry->getManager($entityManagerName);
    }

    /**
     * @param Sticker $sticker
     */
    public function addSticker(Sticker $sticker) {
        $this->entityManager->persist($sticker);
        $this->entityManager->flush();
        $this->versionIncrement();
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function versionIncrement() {
        $stickerVersionRepo = $this->entityManager->getRepository(StickerVersion::class);
        $query = $stickerVersionRepo->createQueryBuilder('sv')
            ->select('COUNT(sv.version) AS amount')
            ->setMaxResults(1)
            ->getQuery();
        $amount = $query->getOneOrNullResult();
        if ($amount['amount'] <= 0) {
            $stickerVersion = new StickerVersion();
            $stickerVersion->version = 0;
            $this->entityManager->persist($stickerVersion);
            $this->entityManager->flush();
        }
        $query = $this->entityManager->createQuery("
            UPDATE \lychee\Module\ContentManagement\Entity\StickerVersion sv
            SET sv.version = sv.version + 1
        ");
        $query->execute();
    }

    /**
     * @param Sticker $sticker
     */
    public function flush(Sticker $sticker) {
        $this->entityManager->flush($sticker);
        $this->versionIncrement();
    }

    /**
     * @param $stickerId
     * @return null|object
     */
    public function fetchById($stickerId) {
        return $this->entityManager->getRepository(Sticker::class)->find($stickerId);
    }

    /**
     * @return array
     */
    public function getStickers() {
        $repository = $this->entityManager->getRepository(Sticker::class);
        $stickers = $repository->findBy([
            'deleted' => 0
        ], [
            'id' => 'DESC'
        ]);
        if (null !== $stickers) {
            return $stickers;
        }

        return [];
    }

    /**
     * @return string
     */
    public function getStickersWithJson() {
        return $this->compactJson($this->getStickers());
    }

    /**
     * @param $stickers
     * @return string
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function compactJson($stickers) {
        $packages = [];

        foreach ($stickers as $sticker) {
            $packages[] = [
                'package_id' => $sticker->id,
                'name' => $sticker->name,
                'thumbnail_url' => $sticker->thumbnailUrl,
                'is_new' => (string)$sticker->isNew,
                'url' => $sticker->url,
                'local_folder_name' => 'biaoqing_' . $sticker->id,
                'stickers' => []
            ];
        }
        $stickerVersionRepo = $this->entityManager->getRepository(StickerVersion::class);
        $query = $stickerVersionRepo->createQueryBuilder('sv')->setMaxResults(1)->getQuery();
        $stickerVersion = $query->getOneOrNullResult();
        if (null === $stickerVersion) {
            $version = 0;
        } else {
            /**
             * @var $stickerVersion \Lychee\Module\ContentManagement\Entity\StickerVersion
             */
            $version = $stickerVersion->version;
        }

        return json_encode([
            'version' => $version,
            'packages' => $packages
        ]);
    }

    /**
     * @return array
     */
    public function fetchAll() {
        return $this->entityManager->getRepository(Sticker::class)->findBy([
            'deleted' => 0
        ]);
    }

    /**
     * 获取贴纸版本号
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getStickerVersion() {
        $query = $this->entityManager->getRepository(StickerVersion::class)->createQueryBuilder('sv')
            ->setMaxResults(1)
            ->getQuery();
        /**
         * @var \Lychee\Module\ContentManagement\Entity\StickerVersion|null $stickerVersion
         */
        $stickerVersion = $query->getOneOrNullResult();

        if (null !== $stickerVersion) {
            return $stickerVersion->version;
        }

        return 0;
    }

    /**
     * 删除贴纸
     * @param $id
     * @return bool
     */
    public function delete($id) {
        /**
         * @var \Lychee\Module\ContentManagement\Entity\Sticker $sticker
         */
        $sticker = $this->entityManager->getRepository(Sticker::class)->find($id);
        if (null === $sticker) {
            return false;
        }
        $sticker->deleted = 1;
        $sticker->lastModifiedTime = new \DateTime();
        $this->entityManager->flush();

        return true;
    }

    /**
     * @param int $page
     * @param int $count
     * @return array
     */
    public function fetchStickers($page = 1, $count = 20) {
        $page < 1 && $page = 1;

        return $this->entityManager->getRepository(Sticker::class)->findBy([
            'deleted' => false
        ], [
            'lastModifiedTime' => 'DESC'
        ], $count, ($page - 1) * $count);
    }
}