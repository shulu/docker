<?php
namespace Lychee\Component\IdGenerator;

class RedisIdGenerator implements IdGenerator {
    /**
     * @var \Predis\Client|redis
     */
    private $redis;

    /**
     * @var int
     */
    private $epoch;

    /**
     * @var string
     */
    private $sequenceKey;

    private $generateCommand;

    /**
     * @param \Predis\Client|redis $redis
     * @param int $epoch
     * @param string $sequenceName
     */
    public function __construct($redis, $epoch, $sequenceName) {
        $this->redis = $redis;
        $this->epoch = $epoch;
        $this->sequenceKey = 'id_seq_'.$sequenceName;
    }

    /**
     * @return int
     */
    public function generate() {
        //FIXME: redis的生成方法要保证没毫秒不能超过1024个id，否则就会出现重复id，需要修正
        if ($this->generateCommand === null) {
            $this->generateCommand = new IdGenerateRedisCommand();
        }
        $this->generateCommand->setArguments(array($this->sequenceKey, $this->epoch));
        return $this->redis->executeCommand($this->generateCommand);
    }
} 