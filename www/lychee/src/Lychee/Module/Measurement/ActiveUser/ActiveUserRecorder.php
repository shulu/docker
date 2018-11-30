<?php
namespace Lychee\Module\Measurement\ActiveUser;

class ActiveUserRecorder {

    private $redis;

    /**
     * @param \Predis\Client|\Redis $redis
     */
    public function __construct($redis) {
        $this->redis = $redis;
    }

    /**
     * @param int $userId
     */
    public function record($userId) {
        try {
            $key = 'active_user_' . date('Ymd');
            $command = new RecordCommand();
            $command->setArguments(array($key, $userId));
            $this->redis->executeCommand($command);
        } catch (\Exception $e) {
            // do nothing.
        }
    }

    /**
     * @param \DateTime $datetime
     * @return int
     */
    public function countActiveUserByDate($datetime) {
        $key = 'active_user_' . $datetime->format('Ymd');
        $count = $this->redis->bitCount($key);
        return $count;
    }

    public function clearActiveUserRecordByDate($datetime) {
        $key = 'active_user_' . $datetime->format('Ymd');
        $this->redis->del($key);
    }
}