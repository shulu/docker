<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2017/4/20
 * Time: 下午2:44
 */

namespace Lychee\Bundle\ApiBundle\Error;
use Lychee\Bundle\ApiBundle\Error\Error;

class PromotionCodeError {

	const CODE_PromotionCodeNotAuthorized = 130001;
	const CODE_PromotionCodeAlreadyReceived = 130002;
	const CODE_PromotionCodeWrongProduct = 130003;
	const CODE_PromotionCodeWrongCode = 130004;
	const CODE_PromotionCodeAlreadyBuy = 130005;
	const CODE_PromotionCodeSelfReceive = 130006;

	static public function notAuthorizedError() {
		$_message = "Not Authorized to Generate Promotion Code";
		$_display = "没有权限生成兑换码";
		return new Error(self::CODE_PromotionCodeNotAuthorized, $_message, $_display);
	}

	static public function alreadyReceived() {
		$_message = "Promotion Code has already received";
		$_display = "礼品码已经被兑换了";
		return new Error(self::CODE_PromotionCodeAlreadyReceived, $_message, $_display);
	}

	static public function wrongProduct() {
		$_message = "Wrong Product";
		$_display = "该商品无法产生礼品码";
		return new Error(self::CODE_PromotionCodeWrongProduct, $_message, $_display);
	}

	static public function wrongCode() {
		$_message = "Wrong Code";
		$_display = "礼品码兑换失败，请检查礼品码是否正确或该渠道的码是否过期。";
		return new Error(self::CODE_PromotionCodeWrongCode, $_message, $_display);
	}

	static public function alreadyBuy() {
		$_message = "You has already buy this product";
		$_display = "礼品码兑换失败，该礼品码对应章节您已解锁。";
		return new Error(self::CODE_PromotionCodeAlreadyBuy, $_message, $_display);
	}

	static public function selfReceive() {
		$_message = "Can not receive code generated by self";
		$_display = "无法兑换自己的礼品码，快把这个礼品码赠送给基友吧~";
		return new Error(self::CODE_PromotionCodeSelfReceive, $_message, $_display);
	}
}