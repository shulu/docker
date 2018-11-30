<?php
namespace Lychee\Bundle\CoreBundle\Twig;

use Lychee\Component\Foundation\TopicUtility;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\HttpFoundation\Request;

function endWith($string, $end) {
    $l1 = strlen($string);
    $l2 = strlen($end);
    if ($l1 < $l2) {
        return false;
    }
    return substr_compare($string, $end, $l1 - $l2, $l2) == 0;
}

class Extension extends \Twig_Extension implements \Twig_Extension_InitRuntimeInterface, \Twig_Extension_GlobalsInterface  {

    /** @var \Twig_Environment  */
    private $env;

    public function initRuntime(\Twig_Environment $environment) {
        parent::initRuntime($environment);
        $this->env = $environment;
    }

    public function getFilters() {
        return array(
            new \Twig_SimpleFilter('resizeImage', array($this, 'getImageResizeUrl')),
            new \Twig_SimpleFilter('cssColor', array($this, 'cssColor')),
	        new \Twig_SimpleFilter('topicColorFilter', array($this, 'topicColorFilter')),
        );
    }

    public function getFunctions() {
        return array(
            new \Twig_SimpleFunction('avatar', array($this, 'getAvatarUrl')),
            new \Twig_SimpleFunction('timeStr', array($this, 'timeToString')),
            new \Twig_SimpleFunction('userAgent', array($this, 'userAgent')),
        );
    }

    public function getTests() {
        return array(

        );
    }

    public function getGlobals() {
        return array();
    }

    public function getAvatarUrl($url) {
        if (empty($url)) {
            return 'http://dl.ciyocon.com/avatar/default';
        } else {
            return $url;
        }
    }

    public function getImageResizeUrl($url, $width, $height, $format = null) {
        $domain = parse_url($url, PHP_URL_HOST);
        if ($domain == null) {
            return $url;
        } else if (endWith($domain, 'qiniudn.com')
            || endWith($domain, 'dl.ciyo.cn')
            || endWith($domain, 'qn.ciyo.cn')
            || endWith($domain, 'qn.ciyocon.com')
            || endWith($domain, 'dl.ciyocon.com')
        ) {
            if ($height == 0) {
                $url = $url . '?imageView2/2/w/' . $width;
            } else if ($width == 0) {
                $url = $url . '?imageView2/2/h/' . $height;
            } else {
                $url = $url . '?imageView2/1/w/' . $width . '/h/' . $height;
            }
            if ($format !== null && in_array($format, array('jpg', 'png', 'gif'))) {
                $url = $url . '/format/'.$format;
            }
            return $url;
        } else if (endWith($domain, 'img.ciyocon.com') || endWith($domain, 'img.ciyo.cn')) {
            if ($height == 0) {
                return $url . '/thumbnail/' . $width;
            } else if ($width == 0) {
                return $url . '/thumbnail/x' . $height;
            } else {
                return $url . '/thumbnail/' . $width . 'x' . $height . '_center';
            }
        } else {
            return $url;
        }
    }

    public function timeToString($time) {
        $now = time();
        $diff = $now - $time;
        if ($diff < 60) {
            return '刚刚';
        } else if ($diff < 3600) {
            return floor($diff / 60) . '分钟前';
        } else if ($diff < 24 * 3600) {
            $sameDay = date('d', $now) == date('d', $time);
            if ($sameDay) {
                return date('今天      H:i', $time);
            } else {
                return date('昨天      H:i', $time);
            }
        } else {
            $sameYear = date('Y', $now) == date('Y', $time);
            if ($sameYear) {
                return date('m月d日      H:i', $time);
            } else {
                return date('Y年m月d日      H:i', $time);
            }
        }
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function isAndroid($request) {
        $userAgent = $request->server->get('HTTP_USER_AGENT');
        if (preg_match('/Android/i', $userAgent)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function isWechat($request) {
        $userAgent = $request->server->get('HTTP_USER_AGENT');
        if (preg_match('/MicroMessenger/i', $userAgent)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function isIE($request) {
        $userAgent = $request->server->get('HTTP_USER_AGENT');
        if (preg_match('/MSIE/i', $userAgent)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function isIOS($request) {
        $userAgent = $request->server->get('HTTP_USER_AGENT');
        if (preg_match('/iPhone|iPad|iPod|iOS/i', $userAgent)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function isCiyo($request) {
        $userAgent = $request->server->get('HTTP_USER_AGENT');
        if (preg_match('/ciyocon/i', $userAgent)) {
            return true;
        } else {
            return false;
        }
    }

    private $_ua = null;
    public function userAgent() {
        if ($this->_ua == null) {
            /** @var AppVariable $app */
            $app = $this->env->getGlobals()['app'];
            if (isset($app) && isset($app->getRequest()->server)) {
                $uaStr = $app->getRequest()->server->get('HTTP_USER_AGENT');
            } else {
                $uaStr = '';
            }
            $this->_ua = new UserAgent($uaStr);
        }
        return $this->_ua;
    }

    /**
     * @param string $color
     * @return string
     */
    public function cssColor($color) {
        $c = intval($color, 16);
        if ($c & 0xFF000000) {
            $r = $c >> 24;
            $g = ($c & 0xff0000) >> 16;
            $b = ($c & 0xff00) >> 8;
            $a = round(($c & 0xff) / 255, 2);
            return "rgba($r, $g, $b, $a)";
        } else {
            $r = $c >> 16;
            $g = ($c & 0xff00) >> 8;
            $b = ($c & 0xff);
            return "rgb($r, $g, $b)";
        }
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName() {
        return 'lychee_extension';
    }

	/**
	 * @param $color
	 *
	 * @return string
	 */
    public function topicColorFilter($color) {
    	return TopicUtility::filterColor($color);
    }

}