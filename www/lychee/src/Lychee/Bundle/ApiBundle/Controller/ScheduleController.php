<?php
namespace Lychee\Bundle\ApiBundle\Controller;

use Lychee\Bundle\ApiBundle\Error\CommonError;
use Lychee\Bundle\ApiBundle\Error\ScheduleError;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Lychee\Module\Schedule\ScheduleService;

class ScheduleController extends Controller {

    /**
     * @return ScheduleService
     */
    private function scheduleService() {
        return $this->get('lychee.module.schedule');
    }

    /**
     * @Route("/schedule/join")
     * @Method("post")
     * @ApiDoc(
     *   section="schedule",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="schedule", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function join(Request $request) {
        $account = $this->requireAuth($request);
        $scheduleId = $this->requireId($request->request, 'schedule');

        $service = $this->scheduleService();
        $schedule = $service->fetchOne($scheduleId);
        if ($schedule == null) {
            return $this->errorsResponse(ScheduleError::ScheduleNonExist());
        }
        if ($schedule->cancelled) {
            return $this->errorsResponse(ScheduleError::ScheduleCancelled());
        }
        $now = new \DateTime();
        if ($now >= $schedule->endTime) {
            return $this->errorsResponse(ScheduleError::ScheduleEnded());
        }

        $service->join($account->id, $scheduleId);

        return $this->sucessResponse();
    }

    /**
     * @Route("/schedule/unjoin")
     * @Method("post")
     * @ApiDoc(
     *   section="schedule",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="schedule", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function unjoin(Request $request) {
        $account = $this->requireAuth($request);
        $scheduleId = $this->requireId($request->request, 'schedule');

        $service = $this->scheduleService();
        $schedule = $service->fetchOne($scheduleId);
        if ($schedule == null) {
            return $this->errorsResponse(ScheduleError::ScheduleNonExist());
        }
        if ($schedule->cancelled) {
            return $this->errorsResponse(ScheduleError::ScheduleCancelled());
        }
        $now = new \DateTime();
        if ($now >= $schedule->startTime) {
            return $this->errorsResponse(ScheduleError::ScheduleStarted());
        }

        $service->unjoin($account->id, $scheduleId);

        return $this->sucessResponse();
    }

    /**
     * @Route("/schedule/cancel")
     * @Method("post")
     * @ApiDoc(
     *   section="schedule",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="schedule", "dataType"="integer", "required"=true}
     *   }
     * )
     */
    public function cancel(Request $request) {
        $account = $this->requireAuth($request);
        $scheduleId = $this->requireId($request->request, 'schedule');

        $service = $this->scheduleService();
        $schedule = $service->fetchOne($scheduleId);
        if ($schedule == null) {
            return $this->errorsResponse(ScheduleError::ScheduleNonExist());
        }

        if ($schedule->creatorId != $account->id) {
            return $this->errorsResponse(CommonError::PermissionDenied());
        }

        if ($schedule->cancelled) {
            return $this->sucessResponse();
        }

        $now = new \DateTime();
        if ($now >= $schedule->startTime) {
            return $this->errorsResponse(ScheduleError::ScheduleStarted());
        }

        $service->cancel($scheduleId, $account->id);
        return $this->sucessResponse();
    }

    /**
     * @Route("/schedule/joiners")
     * @Method("get")
     * @ApiDoc(
     *   section="schedule",
     *   parameters={
     *     {"name"="access_token", "dataType"="string", "required"=true},
     *     {"name"="schedule", "dataType"="integer", "required"=true},
     *     {"name"="cursor", "dataType"="string", "required"=false},
     *     {"name"="count", "dataType"="integer", "required"=false,
     *       "description"="每次返回的数据，默认20，最多不超过50"}
     *   }
     * )
     */
    public function getJoiners(Request $request) {
        $account = $this->requireAuth($request);
        $scheduleId = $this->requireId($request->query, 'schedule');
        list($cursor, $count) = $this->getStringCursorAndCount($request->query, 20, 50);

        $service = $this->scheduleService();
        $joinerIds = $service->getJoinerIds($scheduleId, $cursor, $count, $nextCursor);

        $synthesizer = $this->getSynthesizerBuilder()->buildUserSynthesizer($joinerIds, $account->id);
        return $this->arrayResponse('users', $synthesizer->synthesizeAll(), $nextCursor);
    }

}