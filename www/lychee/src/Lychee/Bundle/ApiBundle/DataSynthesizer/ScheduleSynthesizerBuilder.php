<?php
namespace Lychee\Bundle\ApiBundle\DataSynthesizer;

use Lychee\Module\Schedule\ScheduleService;
use Lychee\Bundle\ApiBundle\DataSynthesizer\SynthesizerBuilder;

class ScheduleSynthesizerBuilder extends AbstractSynthesizerBuilder {

    public function build($idsOrEntities, $accountId = 0, $options = null) {
        /** @var ScheduleService $scheduleService */
        $scheduleService = $this->container()->get('lychee.module.schedule');

        list($sids, $schedules) = $this->extractIdsAndEntities($idsOrEntities, function($ids) use($scheduleService) {
            return $scheduleService->fetch($ids);
        });

        $joinerIdsBySid = array();
        $allJoinerIds = array();
        foreach ($sids as $sid) {
            $joinerIds = $scheduleService->getJoinerIds($sid, 0, 10);
            $allJoinerIds = array_merge($allJoinerIds, $joinerIds);
            $joinerIdsBySid[$sid] = $joinerIds;
        }

        /** @var SynthesizerBuilder $synthesizerBuilder */
        $synthesizerBuilder = $this->container()->get('lychee_api.synthesizer_builder');
        $userSynthesizer = $synthesizerBuilder->buildSimpleUserSynthesizer($allJoinerIds);

        $listSynthesizer = new ListSynthesizer($joinerIdsBySid, $userSynthesizer);

        if ($accountId > 0) {
            $joinResolver = $scheduleService->buildJoinResolver($accountId, $sids);
        } else {
            $joinResolver = null;
        }

        return new ScheduleSynthesizer($schedules, $listSynthesizer, $joinResolver);
    }

}