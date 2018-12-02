<?php
namespace Lychee\Component\Foundation;

class StringUtility {
    /**
     * @param string $string
     *
     * @return bool
     */
    public static function isJsonString($string) {
        json_decode($string);
        if (json_last_error() != JSON_ERROR_NONE) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    public static function isUtf8Encoding($string) {
        return mb_check_encoding($string, 'utf8');
    }

	/**
	 * @param string $haystack
	 * @param string $needle
	 *
	 * @return bool
	 */
	public static function startsWith($haystack, $needle)
	{
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}

	/**
	 * @param string $haystack
	 * @param string $needle
	 *
	 * @return bool
	 */
	public static function endsWith($haystack, $needle)
	{
		$length = strlen($needle);
		if ($length == 0) {
			return true;
		}

		return (substr($haystack, -$length) === $needle);
	}

	public static function generateRandomString( $length ) {
		$token = "";
		$codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
		$codeAlphabet.= "0123456789";
		$max = strlen($codeAlphabet); // edited

		for ($i=0; $i < $length; $i++) {
			$token .= $codeAlphabet[self::crypto_rand_secure(0, $max-1)];
		}
		return $token;
	}

	private static function crypto_rand_secure($min, $max)
	{
		$range = $max - $min;
		if ($range < 1) return $min; // not so random...
		$log = ceil(log($range, 2));
		$bytes = (int) ($log / 8) + 1; // length in bytes
		$bits = (int) $log + 1; // length in bits
		$filter = (int) (1 << $bits) - 1; // set all lower bits to 1
		do {
			$rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
			$rnd = $rnd & $filter; // discard irrelevant bits
		} while ($rnd > $range);
		return $min + $rnd;
	}

    public static function isPhoneValid($areaCode, $phone) {
        if (ctype_digit($areaCode) === false || ctype_digit($phone) === false) {
            return false;
        }

        $areaCodeLength = strlen($areaCode);
        $phoneLength = strlen($phone);

        if ($areaCodeLength != 2 || $phoneLength != 11) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 生成唯一id
     * @param $salt
     * @return string
     */
    public static function generateUniqueId($salt=null) {
        $shellpid = getmypid();
        if (empty($shellpid)) {
            $shellpid = 0;
        }
        $servip = '0';
        if (isset($_SERVER['SERVER_ADDR'])) {
            $servip = $_SERVER['SERVER_ADDR'];
        }
        if (is_null($salt)) {
            $salt = mt_rand(1, 999);
        }
        return md5($salt.'|'.$servip.'|'.$shellpid.'|'.microtime());
    }

}