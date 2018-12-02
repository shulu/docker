<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 07/12/2016
 * Time: 4:05 PM
 */

namespace Lychee\Bundle\ApiBundle\DataSynthesizer;


use Lychee\Module\Game\Entity\GameCategory;

class GameCategorySynthesizer extends AbstractSynthesizer {

	/**
	 * @param GameCategory $entity
	 * @param null $info
	 *
	 * @return array
	 */
	protected function synthesize( $entity, $info = null ) {
		return [
			'id' => $entity->id,
			'icon' => $entity->icon,
			'name' => $entity->name,
		];
	}
}