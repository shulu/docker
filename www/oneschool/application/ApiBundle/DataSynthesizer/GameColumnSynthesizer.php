<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 07/12/2016
 * Time: 4:31 PM
 */

namespace Lychee\Bundle\ApiBundle\DataSynthesizer;


use Lychee\Module\Game\Entity\GameColumns;

class GameColumnSynthesizer extends AbstractSynthesizer {

	/**
	 * @var GameSynthesizer
	 */
	private $gameSynthesizer;

	public function __construct($entitiesByIds, GameSynthesizer $gameSynthesizer) {
		parent::__construct( $entitiesByIds );
		$this->gameSynthesizer = $gameSynthesizer;
	}

	/**
	 * @param GameColumns $entity
	 * @param null $info
	 *
	 * @return array
	 */
	protected function synthesize($entity, $info = null) {
		return [
			'id' => $entity->id,
			'title' => $entity->title,
			'apps' => $this->gameSynthesizer->synthesizeAll(),
		];
	}
}