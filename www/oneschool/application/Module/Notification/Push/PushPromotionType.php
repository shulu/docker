<?php
namespace Lychee\Module\Notification\Push;

abstract class PushPromotionType {
    const USER = 1;
    const POST = 2;
    const COMMENT = 3;
    const TOPIC = 4;

    /**
     * @param $string
     * @return int
     * @throws \InvalidArgumentException
     */
    static public function fromString($string) {
        switch ($string) {
            case 'user':
                return self::USER;
            case 'post':
                return self::POST;
            case 'comment':
                return self::COMMENT;
            case 'topic':
                return self::TOPIC;
            default:
                throw new \InvalidArgumentException($string);
        }
    }

    /**
     * @param int $type
     * @return string
     * @throws \InvalidArgumentException
     */
    static public function toString($type) {
        switch ($type) {
            case self::USER:
                return 'user';
            case self::POST:
                return 'post';
            case self::COMMENT:
                return 'comment';
            case self::TOPIC:
                return 'topic';
            default:
                throw new \InvalidArgumentException($type);
        }
    }
} 