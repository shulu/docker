<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 1/22/16
 * Time: 6:34 PM
 */

namespace Lychee\Module\ContentAudit;

use Doctrine\ORM\EntityManager;
use Lychee\Module\ContentAudit\Entity\AntiRubbish;
use Lychee\Module\ContentAudit\Entity\AuditImage;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ContentAuditService {

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * ContentAuditService constructor.
     * @param RegistryInterface $doctrineRegistry
     * @param string $entityManagerName
     */
    public function __construct($doctrineRegistry, $entityManagerName) {
        $this->entityManager = $doctrineRegistry->getManager($entityManagerName);
    }

    public function deleteAuditPost($postId) {
        $repo = $this->entityManager->getRepository(AuditImage::class);
        $result = $repo->findBy([
            'postId' => $postId
        ]);
        if (is_array($result)) {
            foreach ($result as $row) {
                $this->entityManager->remove($row);
            }
            $this->entityManager->flush();
        }
    }

    public function deleteAuditImg($imgUrl) {
        $repo = $this->entityManager->getRepository(AuditImage::class);
        $result = $repo->findBy([
            'imageUrl' => $imgUrl
        ]);
        if (is_array($result) && !empty($result)) {
            foreach ($result as $row) {
                $this->entityManager->remove($row);
            }
            $this->entityManager->flush();
        }
    }

    /**
     * @param $auditId
     * @return null|object
     */
    public function fetchAuditImg($auditId) {
        /**
         * @var $em \Doctrine\ORM\EntityManager
         */
        $em = $this->entityManager;
        $repo = $em->getRepository(AuditImage::class);
        
        return $repo->find($auditId);
    }

    /**
     * @param $page
     * @param $count
     * @return array
     */
    public function fetchAntiRubbish($page, $count) {
        $offset = ($page - 1) * $count;
        $repo = $this->entityManager->getRepository(AntiRubbish::class);
        $query = $repo->createQueryBuilder('a')
            ->orderBy('a.id', 'DESC')
            ->setMaxResults($count)
            ->setFirstResult($offset)
            ->getQuery();
        return $query->getResult();
    }

    public function fetchAntiRubbishByTime($startTime, $endTime, $page, $count = 20) {
        $offset = ($page - 1) * $count;
        $repo = $this->entityManager->getRepository(AntiRubbish::class);
        $query = $repo->createQueryBuilder('a')
            ->where('a.createTime<:startTime')
            ->andWhere('a.createTime>=:endTime')
            ->orderBy('a.id', 'DESC')
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime)
            ->setMaxResults($count)
            ->setFirstResult($offset)
            ->getQuery();
        return $query->getResult();
    }

    /**
     * @return null
     */
    public function antiRubbishCount() {
        $conn = $this->entityManager->getConnection();
        $stmt = $conn->prepare('SELECT COUNT(id) rubbish_count FROM anti_rubbish');
        $stmt->execute();
        $result = $stmt->fetch();

        if ($result) {
            return $result['rubbish_count'];
        } else {
            return null;
        }
    }

    public function antiRubbishCountByTime($startTime, $endTime) {
        $conn = $this->entityManager->getConnection();
        $stmt = $conn->prepare(
            'SELECT COUNT(id) rubbish_count FROM anti_rubbish
            WHERE create_time<:startTime AND create_time>=:endTime'
        );
        $stmt->bindValue(':startTime', $startTime);
        $stmt->bindValue(':endTime', $endTime);
        $stmt->execute();
        $result = $stmt->fetch();

        if ($result) {
            return $result['rubbish_count'];
        } else {
            return null;
        }
    }

    public function removeUserFromAntiRubbish($userId) {
        $result = $this->entityManager->getRepository(AntiRubbish::class)
            ->findBy(['userId' => $userId]);
        if ($result) {
            foreach ($result as $row) {
                $this->entityManager->remove($row);
            }
            $this->entityManager->flush();
        }
    }
}