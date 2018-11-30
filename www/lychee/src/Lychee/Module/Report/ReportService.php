<?php
namespace Lychee\Module\Report;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Lychee\Module\Report\Entity\CommentReport;
use Lychee\Module\Report\Entity\PostReport;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Lychee\Component\Foundation\CursorableIterator\QueryCursorableIterator;
use Lychee\Component\Foundation\CursorableIterator\CursorableIterator;

class ReportService {
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @param RegistryInterface $registry
     * @param string $emName
     */
    public function __construct($registry, $emName) {
        $this->em = $registry->getManager($emName);
    }

    /**
     * @param int $reporterId
     * @param int $postId
     */
    public function reportPost($reporterId, $postId) {
        try {
            $report = new PostReport();
            $report->postId = $postId;
            $report->reporterId = $reporterId;
            $report->time = new \DateTime();
            $this->em->persist($report);
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            //do nothing;
        }
    }

    public function countPostReports($postId) {
        $dql = 'SELECT COUNT(r.id) FROM '.PostReport::class.' r WHERE r.postId = :postId';
        $query = $this->em->createQuery($dql);
        $query->setParameter('postId', $postId);
        return intval($query->getSingleScalarResult());
    }

    /**
     * @param int $reporterId
     * @param int $commentId
     */
    public function reportComment($reporterId, $commentId) {
        try {
            $report = new CommentReport();
            $report->commentId = $commentId;
            $report->reporterId = $reporterId;
            $report->time = new \DateTime();
            $this->em->persist($report);
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            //do nothing;
        }
    }

    /**
     * @return CursorableIterator
     */
    public function reportPostsIterator() {
        $dql = 'SELECT r FROM '.PostReport::class.' r WHERE r.id < :cursor ORDER BY r.id DESC';
        $query = $this->em->createQuery($dql);
        $iterator = new QueryCursorableIterator($query, 'id', null, QueryCursorableIterator::ORDER_DESC);
        return $iterator;
    }

    /**
     * @return CursorableIterator
     */
    public function reportCommentsIterator() {
        $dql = 'SELECT r FROM '.CommentReport::class.' r WHERE r.id < :cursor ORDER BY r.id DESC';
        $query = $this->em->createQuery($dql);
        $iterator = new QueryCursorableIterator($query, 'id', null, QueryCursorableIterator::ORDER_DESC);
        return $iterator;
    }

} 