<?php
namespace Lychee\Bundle\ApiBundle\Error;

use Lychee\Bundle\ApiBundle\Error\Error;

class PaymentError {
    const CODE_AppStoreCouldNotReadJson = 110001;
    const CODE_ReceiptDataWasMalformedOrMissing = 110002;
    const CODE_ReceiptNotAuthenticated = 110003;
    const CODE_SharedSecretNotMatch = 110004;
    const CODE_ReceiptServerNotCurrentlyAvailable = 110005;
    const CODE_SubscriptionHasExpired = 110006;
    const CODE_ReceiptIsFromTheTestEnv = 110007;
    const CODE_ReceiptIsFromTheProdEnv = 110008;
    const CODE_ReceiptAlreadyVerified = 110101;
    const CODE_OrderNotFound = 110201;
    const CODE_TransactionAlreadyFinished = 110202;
    const CODE_TransactionAlreadyExisted = 110203;
    const CODE_ProductNotFound = 110204;
    const CODE_InvalidTransaction = 110205;
	const CODE_TransactionNotFound = 110206;

    static public function AppStoreCouldNotReadJson() {
        $_message = "The App Store could not read the JSON object you provided";
        $_display = null;
        return new Error(self::CODE_AppStoreCouldNotReadJson, $_message, $_display);
    }

    static public function ReceiptDataWasMalformedOrMissing() {
        $_message = "The data in the receipt-data property was malformed or missing";
        $_display = null;
        return new Error(self::CODE_ReceiptDataWasMalformedOrMissing, $_message, $_display);
    }

    static public function ReceiptNotAuthenticated() {
        $_message = "The receipt could not be authenticated";
        $_display = null;
        return new Error(self::CODE_ReceiptNotAuthenticated, $_message, $_display);
    }

    static public function SharedSecretNotMatch() {
        $_message = "The shared secret you provided does not match the shared secret on file for your account";
        $_display = null;
        return new Error(self::CODE_SharedSecretNotMatch, $_message, $_display);
    }

    static public function ReceiptServerNotCurrentlyAvailable() {
        $_message = "The receipt server is not currently available";
        $_display = null;
        return new Error(self::CODE_ReceiptServerNotCurrentlyAvailable, $_message, $_display);
    }

    static public function SubscriptionHasExpired() {
        $_message = "This receipt is valid but the subscription has expired. When this status code is returned to your server, the receipt data is also decoded and returned as part of the response";
        $_display = null;
        return new Error(self::CODE_SubscriptionHasExpired, $_message, $_display);
    }

    static public function ReceiptIsFromTheTestEnv() {
        $_message = "This receipt is from the test environment, but it was sent to the production environment for verification. Send it to the test environment instead";
        $_display = null;
        return new Error(self::CODE_ReceiptIsFromTheTestEnv, $_message, $_display);
    }

    static public function ReceiptIsFromTheProdEnv() {
        $_message = "This receipt is from the production environment, but it was sent to the test environment for verification. Send it to the production environment instead";
        $_display = null;
        return new Error(self::CODE_ReceiptIsFromTheProdEnv, $_message, $_display);
    }

    static public function ReceiptAlreadyVerified() {
        $_message = "Receipt already verified";
        $_display = null;
        return new Error(self::CODE_ReceiptAlreadyVerified, $_message, $_display);
    }

    static public function OrderNotFound() {
        $_message = "Order not found";
        $_display = "不存在的订单";
        return new Error(self::CODE_OrderNotFound, $_message, $_display);
    }

    static public function TransactionAlreadyFinished() {
        $_message = "Transaction already finished";
        $_display = "订单已经完成";
        return new Error(self::CODE_TransactionAlreadyFinished, $_message, $_display);
    }

    static public function TransactionAlreadyExisted() {
        $_message = "Transaction already existed";
        $_display = "订单已经存在";
        return new Error(self::CODE_TransactionAlreadyExisted, $_message, $_display);
    }

    static public function ProductNotFound($productId) {
        $_message = "Product not found ".$productId;
        $_display = "商品不存在";
        return new Error(self::CODE_ProductNotFound, $_message, $_display);
    }

    static public function InvalidTransaction() {
        $_message = "Invalid transaction";
        $_display = "非法的订单";
        return new Error(self::CODE_InvalidTransaction, $_message, $_display);
    }

	static public function TransactionNotFound() {
		$_message = "Transaction not found";
		$_display = "交易不存在";
		return new Error(self::CODE_TransactionNotFound, $_message, $_display);
	}
}