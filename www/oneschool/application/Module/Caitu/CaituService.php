<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 16/01/2017
 * Time: 8:02 PM
 */

namespace Lychee\Module\Caitu;


use Doctrine\ORM\EntityManagerInterface;
use Lychee\Module\Caitu\Entity\CaituRecord;
use Lychee\Module\ExtraMessage\Entity\Contacts;
use Lychee\Module\ExtraMessage\Entity\EMClientVersion;
use Lychee\Module\ExtraMessage\Entity\EMDiary;
use Lychee\Module\ExtraMessage\Entity\EMDictionary;
use Lychee\Module\ExtraMessage\Entity\EMPaymentComment;
use Lychee\Module\ExtraMessage\Entity\EMPictureRecord;
use Lychee\Module\ExtraMessage\Entity\EMPromotionCode;
use Lychee\Module\ExtraMessage\Entity\Options;
use Lychee\Module\ExtraMessage\Entity\Plays;
use Lychee\Module\ExtraMessage\Entity\EMGameRecord;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class CaituService
 * @package Lychee\Module\ExtraMessage
 */
class CaituService {

	/**
	 * @var EntityManagerInterface
	 */
	private $em;

	/**
	 * ExtraMessageService constructor.
	 *
	 * @param RegistryInterface $doctrine
	 */
	public function __construct(RegistryInterface $doctrine) {
		$this->em = $doctrine->getManager();
	}

	public function findByPhone($phone){

		$result = $this->em->getRepository(CaituRecord::class)->findOneBy(
			array(
				'phone' => $phone
			)
		);

		return $result;
	}

	public function addRecord($phone, $state, $ddate, $tdate, $extra, $fee, $type){

		$record = $this->findByPhone($phone);

		if(empty($record)){
			$record = new CaituRecord();
			$record->phone = $phone;
		}

		$record->state = $state;
		$record->ddate = new \DateTime($ddate);
		$record->tdate = new \DateTime($tdate);
		$record->extra = $extra;
		$record->fee = $fee;
		$record->type = $type;

		$this->em->persist($record);
		$this->em->flush();
	}
}