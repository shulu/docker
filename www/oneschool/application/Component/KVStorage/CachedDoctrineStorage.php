<?php
namespace Lychee\Component\KVStorage;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Lychee\Component\Foundation\ArrayUtility;

class CachedDoctrineStorage implements Reader, Writer {

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var string
     */
    private $entityName;

    /**
     * @var ClassMetadata
     */
    private $metaData;

    /**
     * @var Reader|Writer
     */
    private $cacheStorage;

    /**
     * @var string
     */
    private $idFieldName;

    /**
     * @param EntityManager $entityManager
     * @param string $entityName
     * @param Reader|Writer $cacheStorage
     */
    public function __construct($entityManager, $entityName, $cacheStorage) {
        assert($cacheStorage !== null);

        $this->entityManager = $entityManager;
        $this->entityName = $entityName;
        $this->cacheStorage = $cacheStorage;
        $this->metaData = $this->entityManager->getClassMetadata($this->entityName);
        //make sure it is single identitier
        assert($this->metaData->isIdentifierComposite === false);
        $this->idFieldName = $this->metaData->getSingleIdentifierFieldName();
    }

    /**
     * @param string $id
     *
     * @return mixed|null
     */
    public function get($id) {
        $managedEntity = $this->entityManager->getUnitOfWork()->tryGetById(
            array($this->metaData->identifier[0] => $id),
            $this->metaData->rootEntityName
        );
        $className = $this->metaData->name;
        if ($managedEntity !== false && ($managedEntity instanceof $className)) {
            return $managedEntity;
        }

        $cachedValue = $this->cacheStorage->get($id);
        if ($cachedValue !== null) {
            $this->registerManaged($cachedValue);
            return $cachedValue;
        } else {
            $value = $this->entityManager->find($this->entityName, $id);
            if ($value !== null && $this->cacheStorage instanceof Writer) {
                $this->cacheStorage->set($id, $value);
            }
            return $value;
        }
    }

    /**
     * @param string $id
     *
     * @return mixed|null
     */
    public function getReal($id) {
        return $this->entityManager->find($this->entityName, $id);
    }

    /**
     * @param array $ids
     *
     * @return array 返回形如[key1: value1, key2: value2, key3: value3]格式的数据，注意是一个关联数组
     */
    public function getMulti($ids) {
        if (count($ids) === 0) {
            return array();
        }

        $cachedValues = $this->cacheStorage->getMulti($ids);
        foreach ($cachedValues as $cachedValue) {
            $this->registerManaged($cachedValue);
        }

        if (count($cachedValues) === count($ids)) {
            return $cachedValues;
        } else {
            $missKeys = array_diff($ids, array_keys($cachedValues));
            $entities = $this->entityManager
                ->getRepository($this->entityName)
                ->findBy(array($this->idFieldName => $missKeys));
            $result = array();
            foreach ($entities as $entity) {
                $id = $this->metaData->getFieldValue($entity, $this->idFieldName);
                $result[$id] = $entity;
            }

            if (count($result) > 0 && $this->cacheStorage instanceof Writer) {
                $this->cacheStorage->setMulti($result);
            }

            return ArrayUtility::mergeKeepKeys($cachedValues, $result);
        }
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
        if ($id === null) {
            $id = $this->metaData->getFieldValue($entity, $this->idFieldName);
        }
        $this->cacheStorage->set($id, $entity);
        $this->cacheStorage->delete($id);
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

        $cacheValues = array();
        foreach ($idsAndEntities as $entity) {
            $id = $this->metaData->getFieldValue($entity, $this->idFieldName);
            $cacheValues[$id] = $entity;
        }
        $this->cacheStorage->setMulti($cacheValues);
    }

    private function registerManaged($entity) {
        $entityState = $this->entityManager->getUnitOfWork()->getEntityState($entity, UnitOfWork::STATE_DETACHED);
        if ($entityState != UnitOfWork::STATE_DETACHED) {
            return;
        }

        $data = array();
        foreach ($this->metaData->getFieldNames() as $filedName) {
            $data[$filedName] = $this->metaData->getFieldValue($entity, $filedName);
        }

        //it must be single identifier, and no association mapping on identifier
        if (isset($this->metaData->associationMappings[$this->metaData->identifier[0]])) {
            $id = array($this->metaData->identifier[0] => $data[$this->metaData->associationMappings[$this->metaData->identifier[0]]['joinColumns'][0]['name']]);
        } else {
            $id = array($this->metaData->identifier[0] => $data[$this->metaData->identifier[0]]);
        }

        $this->entityManager->getUnitOfWork()->registerManaged($entity, $id, $data);
    }

    /**
     * @param string $key
     */
    public function delete($key) {
        $this->cacheStorage->delete($key);
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
        $this->cacheStorage->deleteMulti($keys);
        $query = $this->entityManager->createQuery('
            DELETE '.$this->entityName.' t WHERE t.'.$this->idFieldName.' IN (:ids)
        ');
        $query->execute(array('ids' => $keys));
    }


}