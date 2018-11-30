<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Module\IM\GroupService;

class IMGroupSynthesizerBuilder extends AbstractSynthesizerBuilder {

    /**
     * @param array $idsOrEntities
     * @param int $accountId
     * @param mixed $options
     * @return Synthesizer
     */
    public function build($idsOrEntities, $accountId = 0, $options = null) {
        /** @var GroupService $groupService */
        $groupService = $this->container->get('lychee.module.im.group');
        list($gids, $groups) = $this->extractIdsAndEntities($idsOrEntities, function($ids) use($groupService) {
            try {
                $groups = $groupService->multiGet($ids);
            } catch (\Exception $e) {
                $groups = array();
            }
            return $groups;
        });

        return new IMGroupSynthesizer($groups, null);
    }

}