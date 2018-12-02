<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

abstract class AbstractSynthesizer implements Synthesizer {

    /**
     * @var array
     */
    protected $entitiesByIds;

    public function __construct($entitiesByIds) {
        $this->entitiesByIds = $entitiesByIds;
    }

    /**
     * @param $id
     * @return mixed|null
     */
    public function getEntityById($id) {
        return isset($this->entitiesByIds[$id]) ? $this->entitiesByIds[$id] : null;
    }

    /**
     * @param mixed $entity
     * @param mixed $info
     *
     * @return array
     */
    abstract protected function synthesize($entity, $info = null);

    /**
     * @param mixed $info
     * @return array
     */
    public function synthesizeAll($info = null) {
        if (empty($this->entitiesByIds)) {
            return array();
        }

        $result = array();
        foreach ($this->entitiesByIds as $entity) {
            $data = $this->synthesize($entity, $info);
            if ($data !== null) {
                $result[] = $data;
            }
        }
        return $result;
    }

    /**
     * @param int $id
     * @param mixed $info
     *
     * @return array
     */
    public function synthesizeOne($id, $info = null) {
        if (isset($this->entitiesByIds[$id])) {
            $entity = $this->entitiesByIds[$id];
            return $this->synthesize($entity, $info);
        } else {
            return null;
        }
    }

    /**
     * @param int[] $ids
     * @param mixed $info
     *
     * @return array
     */
    public function synthesizeMany($ids, $info = null) {
        return array_map(function($id) use ($info) {
            return $this->synthesizeOne($id, $info);
        }, $ids);
    }

} 