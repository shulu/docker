<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 5/27/15
 * Time: 3:30 PM
 */

namespace Lychee\Module\ContentManagement;


use Lychee\Module\ContentManagement\Entity\SearchRecord;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Lychee\Module\ContentManagement\Entity\SearchKeyword;

/**
 * Class SearchKeywordManagement
 * @package Lychee\Module\ContentManagement
 */
class SearchKeywordManagement {

    /**
     * @var \Doctrine\ORM\EntityManager $entityManager
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
     * @param $keyword
     * @param $searchType
     * @param $userId
     * @throws InvalidSearchTypeException
     */
    public function record($keyword, $searchType, $userId) {
        if ($keyword) {
            if (!in_array($searchType, $this->searchTypes())) {
                throw new InvalidSearchTypeException();
            }
            $searchKeywordRepo = $this->entityManager->getRepository(SearchKeyword::class);
            $searchKeyword = $searchKeywordRepo->findOneBy([
                'keyword' => $keyword,
            ]);
            if (null === $searchKeyword) {
                $searchKeyword = new SearchKeyword();
                $searchKeyword->setKeyword($keyword)
                    ->setLastRecordTime(new \DateTime());
                $this->entityManager->persist($searchKeyword);
                $this->entityManager->flush();
            }
            $searchKeywordId = $searchKeyword->getId();
            $searchRecord = new SearchRecord();
            $searchRecord->setKeywordId($searchKeywordId)
                ->setSearchType($searchType)
                ->setUserId($userId);
            $searchKeyword->setLastRecordTime(new \DateTime());
            $this->entityManager->persist($searchRecord);
            $this->entityManager->flush();
        }
    }

    /**
     * @return array
     */
    private function searchTypes() {
        $reflectionClass = new \ReflectionClass(SearchType::class);

        return $reflectionClass->getConstants();
    }

    /**
     * @param int $page
     * @param int $count
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function fetchLatestPopularKeywords($page = 1, $count = 20) {
        !$page && $page = 1;
        $offset = ($page - 1) * $count;
        $sql = "SELECT r.keyword_id, k.keyword, COUNT(r.id) record_count
            FROM search_record r
            LEFT JOIN search_keyword k ON k.id = r.keyword_id
            GROUP BY r.keyword_id
            ORDER BY record_count DESC
            LIMIT $offset, $count";
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

        return $result;
    }
}