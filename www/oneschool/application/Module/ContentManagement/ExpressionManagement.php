<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 10/28/15
 * Time: 2:19 PM
 */

namespace Lychee\Module\ContentManagement;

use Lychee\Module\ContentManagement\Entity\ExpressionPackageVersion;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Lychee\Module\ContentManagement\Entity\ExpressionPackage;
use Lychee\Module\ContentManagement\Entity\Expression;

/**
 * Class ExpressionManagement
 * @package Lychee\Module\ContentManagement
 */
class ExpressionManagement {

    /**
     * @var \Doctrine\Common\Persistence\ObjectManager $entityManager
     */
    private $entityManager;

    /**
     * @param RegistryInterface $doctrine
     * @param $entityManagerName
     */
    public function __construct(RegistryInterface $doctrine, $entityManagerName) {
        $this->entityManager = $doctrine->getManager($entityManagerName);
    }

    /**
     * @param int $page
     * @param int $count
     * @param bool|false $onlyEnabled
     * @return array
     */
    public function fetchAllExpressionPackages($page = 1, $count = 20, $onlyEnabled = false) {
        $page < 1 && $page = 1;
        $where = [];
        if (true === $onlyEnabled) {
            $where = [
                'deleted' => false
            ];
        }

        return $this->entityManager->getRepository(ExpressionPackage::class)->findBy($where, [
            'lastModifiedTime' => 'DESC'
        ], $count, ($page - 1) * $count);
    }

    /**
     * @param int $page
     * @param int $count
     * @return array
     */
    public function fetchAllEnabledExpressionPackages($page = 1, $count = 20) {
        return $this->fetchAllExpressionPackages($page, $count, true);
    }

    /**
     * @param $packageId
     * @return array
     */
    public function fetchExpressionsByPackageId($packageId) {
        return $this->entityManager->getRepository(Expression::class)->findBy([
            'packageId' => $packageId
        ], [
            'id' => 'ASC'
        ]);
    }

    /**
     * @param array $packageIds
     * @return array
     */
    public function fetchExpressionsByPackageIds($packageIds = []) {
        $expressions = $this->entityManager->getRepository(Expression::class)->findBy([
            'packageId' => $packageIds
        ], [
            'id' => 'ASC'
        ]);
        $packages = [];
        /**
         * @var \Lychee\Module\ContentManagement\Entity\Expression $expression
         */
        foreach ($expressions as $expression) {
            $packages[$expression->getPackageId()][] = $expression;
        }
        $result = [];
        foreach($packageIds as $packageId) {
            $result[$packageId] = $packages[$packageId];
        }

        return $result;
    }

    /**
     * @param $name
     * @param $coverImage
     * @param $packageUrl
     * @return ExpressionPackage
     * @throws \Exception
     */
    public function addExpressionPackage($name, $coverImage, $packageUrl) {
        $package = new ExpressionPackage();
        $package->setName($name);
        $package->setCoverImage($coverImage);
        $package->setDownloadUrl($packageUrl);
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $this->entityManager->persist($package);
            $this->entityManager->flush();
            $this->versionIncrement();
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollback();
            throw $e;
        }

        return $package;
    }

    /**
     * @param $packageId
     * @param array $expressions
     * @throws \Exception
     */
    public function addExpressionsByPackageId($packageId, $expressions = []) {
        if (!empty($expressions)) {
            $this->entityManager->getConnection()->beginTransaction();
            try {
                $this->removeExpressionsByPackageId($packageId);
                foreach ($expressions as $expressionArr) {
                    $expression = new Expression();
                    $expression->setPackageId($packageId);
                    $expression->setImageUrl($expressionArr[0]);
                    $expression->setName($expressionArr[1]);
                    $expression->setFilename($expressionArr[2]);
                    $this->entityManager->persist($expression);
                }
                $this->entityManager->flush();
                $this->entityManager->getConnection()->commit();
            } catch (\Exception $e) {
                $this->entityManager->getConnection()->rollback();
                throw $e;
            }
        }
    }

    /**
     * @param $packageId
     * @param $deleted
     * @throws \Exception
     */
    private function togglePackageStatus($packageId, $deleted) {
        /**
         * @var \Lychee\Module\ContentManagement\Entity\ExpressionPackage $package
         */
        $package = $this->entityManager->getRepository(ExpressionPackage::class)->find($packageId);
        if ($package) {
            $this->entityManager->getConnection()->beginTransaction();
            try {
                $package->setDeleted($deleted);
                $this->entityManager->flush();
                $this->versionIncrement();
                $this->entityManager->getConnection()->commit();
            } catch (\Exception $e) {
                $this->entityManager->getConnection()->rollback();
                throw $e;
            }
        }
    }

    /**
     * @param $packageId
     */
    public function removePackage($packageId) {
        $this->togglePackageStatus($packageId, true);
    }

    /**
     * @param $packageId
     */
    public function recoverPackage($packageId) {
        $this->togglePackageStatus($packageId, false);
    }

    /**
     * @param $packageId
     */
    private function removeExpressionsByPackageId($packageId) {
        $expressions = $this->entityManager->getRepository(Expression::class)->findBy([
            'packageId' => $packageId
        ]);
        foreach ($expressions as $expression) {
            $this->entityManager->remove($expression);
        }
        $this->entityManager->flush();
    }

    /**
     * @param $packageId
     * @return object
     */
    public function fetchOnePackage($packageId) {
        return $this->entityManager->getRepository(ExpressionPackage::class)->find($packageId);
    }

    /**
     * @param $packageId
     * @param $packageName
     * @param null $packageUrl
     * @param null $coverUrl
     */
    public function updatePackage($packageId, $packageName, $packageUrl = null, $coverUrl = null) {
        $package = $this->fetchOnePackage($packageId);
        if ($package) {
            /**
             * @var \Lychee\Module\ContentManagement\Entity\ExpressionPackage $package
             */
            $package->setName($packageName);
            if (null !== $packageUrl) {
                $package->setDownloadUrl($packageUrl);
            }
            if (null !== $coverUrl) {
                $package->setCoverImage($coverUrl);
            }
            $this->entityManager->flush();
            $this->versionIncrement();
        }
    }

    /**
     * @param $name
     * @return object
     */
    public function fetchPackageByName($name) {
        return $this->entityManager->getRepository(ExpressionPackage::class)->findOneBy([
            'name' => $name
        ]);
    }

    protected function versionIncrement() {
        $versionRepo = $this->entityManager->getRepository(ExpressionPackageVersion::class);
        $query = $versionRepo->createQueryBuilder('v')
            ->select('COUNT(v.version) AS amount')
            ->setMaxResults(1)
            ->getQuery();
        $amount = $query->getOneOrNullResult();
        if ($amount['amount'] <= 0) {
            $version = new ExpressionPackageVersion();
            $version->setVersion(0);
            $this->entityManager->persist($version);
            $this->entityManager->flush();
        }
        $query = $this->entityManager->createQuery("
            UPDATE \lychee\Module\ContentManagement\Entity\ExpressionPackageVersion v
            SET v.version = v.version + 1
        ");
        $query->execute();
    }

    /**
     * @return int|mixed
     */
    public function getExpressionPackageVersion() {
        $query = $this->entityManager->getRepository(ExpressionPackageVersion::class)->createQueryBuilder('v')
            ->setMaxResults(1)
            ->getQuery();
        /**
         * @var \Lychee\Module\ContentManagement\Entity\ExpressionPackageVersion|null $version
         */
        $version = $query->getOneOrNullResult();

        if (null !== $version) {
            return $version->getVersion();
        }

        return 0;
    }
}