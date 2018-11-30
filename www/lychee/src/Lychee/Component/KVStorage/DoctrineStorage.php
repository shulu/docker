<?php
namespace Lychee\Component\KVStorage;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;

class DoctrineStorage implements Reader, Writer {

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var string
     */
    private $entityName;

    /**
     * @var ClassMetadata;
     */
    private $metaData;

    /**
     * @var string
     */
    private $idFieldName;

    /**
     * @param EntityManager $entityManager
     * @param string $entityName
     */
    public function __construct($entityManager, $entityName) {
        $this->entityManager = $entityManager;
        $this->entityName = $entityName;

        $this->metaData = $entityManager->getClassMetadata($entityName);
        //make sure entity is single id
        $this->idFieldName = $this->metaData->getSingleIdentifierFieldName();
    }

    /**
     * @param $key
     *
     * @return null|object
     */
    public function get($key) {
        return $this->entityManager->find($this->entityName, $key);
    }

    /**
     * @param array $keys
     *
     * @return array
     */
    public function getMulti($keys) {
        $entities = $this->entityManager
            ->getRepository($this->entityName)
            ->findBy(array($this->idFieldName => $keys));
        $result = array();
        foreach ($entities as $entity) {
            $id = $this->metaData->getFieldValue($entity, $this->idFieldName);
            $result[$id] = $entity;
        }
        return $result;
    }

    /**
     * @param string $id
     * @param mixed $entity
     */
    public function set($id, $entity) {
        $entityState = $this->entityManager->getUnitOfWork()->getEntityState($entity, UnitOfWork::STATE_NEW);
        if ($entityState === UnitOfWork::STATE_NEW) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush($entity);
        } else {
            $this->entityManager->flush($entity);
        }
    }

    /**
     * @param array $idsAndEntities
     */
    public function setMulti($idsAndEntities) {
        $uow = $this->entityManager->getUnitOfWork();

        foreach ($idsAndEntities as $entity) {
            $entityState = $uow->getEntityState($entity, UnitOfWork::STATE_NEW);
            if ($entityState === UnitOfWork::STATE_NEW) {
                $this->entityManager->persist($entity);
            }
        }
        $this->entityManager->flush(array_values($idsAndEntities));
    }

    /**
     * @param string $key
     */
    public function delete($key) {
        $query = $this->entityManager->createQuery('
            DELETE '.$this->entityName.' t WHERE t.'.$this->idFieldName.' = :id
        ');
        $query->execute(array('id' => $key));
    }

    /**
     * @param array $keys
     */
    public function deleteMulti($keys) {
        if (count($keys) == 0) {
            return;
        }
        $query = $this->entityManager->createQuery('
            DELETE '.$this->entityName.' t WHERE t.'.$this->idFieldName.' IN (:ids)
        ');
        $query->execute(array('ids' => $keys));
    }
}