<?php
namespace Lychee\Bundle\ApiBundle\AntiSpam;

class SpamChecker {

    const ACTION_POST = 'post';
    const ACTION_COMMENT = 'comment';
	const ACTION_QQ_SIGNIN = 'qq_signin';

    private $redis;

    /**
     * SpamChecker constructor.
     *
     * @param \Predis\Client|\Redis $redis
     */
    public function __construct($redis) {
        $this->redis = $redis;
    }

    /**
     * @param int $userId
     * @param int $timeWindowInsecond
     * @param int $maxActionPerWindow
     * @param string $action
     * @return bool
     */
    public function check($userId, $timeWindowInsecond, $maxActionPerWindow, $action) {
        $key = 'spam_'.$action.':'.$userId;
        $cmd = new CheckSpamCommand($timeWindowInsecond, $maxActionPerWindow);
        $cmd->setArguments(array($key));
        $actionCountThisTimeWindow = $this->redis->executeCommand($cmd);
        return $actionCountThisTimeWindow > $maxActionPerWindow;
    }

	/**
	 * @param int $ip
	 * @param int $timeWindowInsecond
	 * @param int $maxActionPerWindow
	 * @param string $action
	 * @return bool
	 */
	public function checkIp($ip, $timeWindowInsecond, $maxActionPerWindow, $action) {
		$key = 'spam_'.$action.':'.$ip;
		$cmd = new CheckSpamCommand($timeWindowInsecond, $maxActionPerWindow);
		$cmd->setArguments(array($key));
		$actionCountThisTimeWindow = $this->redis->executeCommand($cmd);
		return $actionCountThisTimeWindow > $maxActionPerWindow;
	}
}