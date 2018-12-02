<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 07/12/2016
 * Time: 12:48 PM
 */

namespace Lychee\Module\Game\Tests;


use Lychee\Component\Test\ModuleAwareTestCase;

class GameColumnsTest extends ModuleAwareTestCase {

	public function testGameColumns() {
		$gameIds = $this->game()->fetchColumnRecommendationList(1, 'android', 0, 20, $nextCursor);
		var_dump($gameIds);
		var_dump($nextCursor);
	}

	public function testGameCategories() {
		$cats = $this->game()->fetchGameCategories();
		var_dump($cats);
	}

	public function testFetchGamesByCat() {
		$catId = 1;
		$platform = 'android';
		$games = $this->game()->fetchGamesByCat($catId, $platform, 0, 2, $nextCursor);
		var_dump($games);
		var_dump($nextCursor);
	}
}