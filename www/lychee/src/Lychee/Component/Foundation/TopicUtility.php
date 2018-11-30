<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 04/11/2016
 * Time: 4:16 PM
 */

namespace Lychee\Component\Foundation;


/**
 * Class TopicUtility
 * @package Lychee\Component\Foundation
 */
class TopicUtility {

	/**
	 * @param $color
	 *
	 * @return string
	 */
	static public function filterColor($color) {
		if (0 === strncmp('0x', $color, 2)) {
			return substr($color, 2);
		} else {
			return $color;
		}
	}
}