<?php
namespace Lychee\Bundle\CoreBundle\Twig;

class UserAgent {

    private $uaString;
    private $resultMap = array();
    private $patternMap = array(
        'android' => '/Android/i',
        'ios' => '/iPhone|iPad|iPod|iOS/i',
        'wechat' => '/MicroMessenger/i',
        'qq' => '/QQ/i',
        'ciyo' => '/ciyocon/i',
        'ie' => '/MSIE/i',
    );

    /**
     * UserAgent constructor.
     * @param string $usString
     */
    public function __construct($usString) {
        $this->uaString = $usString;
    }

    /**
     * @param string $key
     * @return bool
     */
    private function is($key) {
        if (!isset($this->resultMap[$key])) {
            $this->resultMap[$key] = isset($this->patternMap[$key]) ?
                preg_match($this->patternMap[$key], $this->uaString) != 0 : false;
        }
        return $this->resultMap[$key];
    }

    /**
     * @return string
     */
    public function uaStr() {
        return $this->uaString;
    }

    public function android() {
        return $this->is('android');
    }

    public function ios() {
        return $this->is('ios');
    }

    public function wechat() {
        return $this->is('wechat');
    }

    public function qq() {
        return $this->is('qq');
    }

    public function ciyo() {
        return $this->is('ciyo');
    }

    public function ie() {
        return $this->is('ie');
    }

}