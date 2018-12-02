<?php
namespace Lychee\Module\Measurement\ActiveUser;

use Lychee\Component\Task\Task;
use Lychee\Module\Analysis\AnalysisType;
use Lychee\Module\Measurement\ActiveUser\ActiveUserRecorder;
use Lychee\Module\Analysis\AnalysisService;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;

class SettleDailyActiveUserCountTask implements Task {
    use ContainerAwareTrait;
    use ModuleAwareTrait;

    /**
     * @return string
     */
    public function getName() {
        return 'settle-daily-active-user-count';
    }

    /**
     * @return integer
     */
    public function getDefaultInterval() {
        return 9 * 3600;
    }

    /**
     * @return ActiveUserRecorder
     */
    private function getActiveUserRecorder() {
        return $this->container->get('lychee.module.measurement.active_user_recorder');

    }

    /**
     * @return AnalysisService
     */
    private function getAnalysisService() {
        return $this->container->get('lychee.module.analysis');
    }

    /**
     * @return void
     */
    public function run() {
        $recorder = $this->getActiveUserRecorder();
        $yesterday = (new \DateTime())->sub(new \DateInterval('P1D'));
        $count = $recorder->countActiveUserByDate($yesterday);
	    if ($count) {
		    $this->getAnalysisService()->setDailyAnalysis(AnalysisType::ACTIVE_USERS, $yesterday, $count);
		    $recorder->clearActiveUserRecordByDate($yesterday);
	    }
    }

}