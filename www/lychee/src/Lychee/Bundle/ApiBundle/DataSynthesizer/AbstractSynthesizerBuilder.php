<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

abstract class AbstractSynthesizerBuilder {
    use ContainerAwareTrait;
    use ModuleAwareTrait;

    protected function extractIdsAndEntities(
        $entitiesOrIds, $fetchFunction, $idColumnName = 'id'
    ) {
        if (count($entitiesOrIds) == 0) {
            return array(array(), array());
        }

        if (is_object(current($entitiesOrIds))) {
            if (isset($entitiesOrIds[0])) {
                //it is a index base array not a id associate array
                //need to transfomr to id associate array
                $entities = ArrayUtility::mapByColumn($entitiesOrIds, $idColumnName);
                $ids = array_keys($entities);
            } else {
                $ids = array_keys($entitiesOrIds);
                $entities = $entitiesOrIds;
            }
        } else {
            $ids = $entitiesOrIds;
            $entitiesUnordered = $fetchFunction($ids);
            $entities = array();
            foreach ($ids as $id) {
                if (isset($entitiesUnordered[$id])) {
                    $entities[$id] = $entitiesUnordered[$id];
                }
            }
        }

        return array($ids, $entities);
    }

    /**
     * @param array $idsOrEntities
     * @param int $accountId
     * @param mixed $options
     * @return Synthesizer
     */
    abstract public function build($idsOrEntities, $accountId = 0, $options = null);
}