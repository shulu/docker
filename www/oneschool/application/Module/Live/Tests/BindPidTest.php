<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 18/11/2016
 * Time: 6:21 PM
 */

namespace Lychee\Module\Live\Tests;


use Lychee\Component\Test\ModuleAwareTestCase;

class BindPidTest extends ModuleAwareTestCase {

	public function test() {
		$userId = 31724;
		$pid = $this->genPid();
		printf("User: %s\tPID: %s\n", $userId, $pid);

		$this->live()->bindPizusId($userId, $pid);
		$pid = $this->live()->fetchPizusId($userId);
		$strFmt = "Fetch Result: UserId: %s\tPID: %s\n";
		printf($strFmt, $userId, $pid);

		$newPid = $this->genPid();
		printf("User: %s\tPID: %s\n", $userId, $newPid);
		$this->live()->bindPizusId($userId, $newPid);
		$newPid = $this->live()->fetchPizusId($userId);
		printf($strFmt, $userId, $newPid);

		echo '*************************' . PHP_EOL;

		$newUser = 31721;
		printf("Binding User: %s\tPID: %s\n", $newUser, $newPid);
		$this->live()->bindPizusId($newUser, $newPid);
	}

	private function genPid() {
		return mt_rand(100000, 999999);
	}
}