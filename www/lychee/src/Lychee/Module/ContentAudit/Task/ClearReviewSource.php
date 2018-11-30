<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 08/10/2016
 * Time: 6:18 PM
 */

namespace Lychee\Module\ContentAudit\Task;


use Doctrine\ORM\EntityManager;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Task\Task;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ClearReviewSource implements Task {

	use ContainerAwareTrait;
	use ModuleAwareTrait;

	public function getName() {
		return 'clear-review-source';
	}

	public function getDefaultInterval() {
		return 24 * 60 * 60;
	}

	public function run() {
		$oneWeekAgo = new \DateTime('-7 day');
		/** @var EntityManager $em */
		$em = $this->container()->get('doctrine')->getManager();
		$conn = $em->getConnection();
		$stmt = $conn->prepare(
			'DELETE FROM image_review_source
			WHERE review_id IN (SELECT id FROM image_review WHERE last_review_time<:lastReviewTime)'
		);
		$stmt->bindValue(':lastReviewTime', $oneWeekAgo->format('Y-m-d'));
		$stmt->execute();
		$rows = $stmt->rowCount();
		printf("Clear Review Source: Before [%s]: %s\n", $oneWeekAgo->format('Y-m-d'), $rows);
		$oneMonthAgo = new \DateTime('-1 month');
		$stmt = $conn->prepare('DELETE FROM image_review WHERE last_review_time<:lastReviewTime');
		$stmt->bindValue(':lastReviewTime', $oneMonthAgo->format('Y-m-d'));
		$stmt->execute();
		$rows = $stmt->rowCount();
		printf("Clear Review: Before [%s]: %s\n", $oneMonthAgo->format('Y-m-d'), $rows);
	}
}