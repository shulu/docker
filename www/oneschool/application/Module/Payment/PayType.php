<?php


namespace Lychee\Module\Payment;


interface PayType {
    const ALIPAY = 'alipay';
    const WECHAT = 'wechat';
    const QPAY = 'qpay';
	const BILIBILI = 'bilibili';
	const DMZJ = 'dmzj';
	const GOOGLE = 'google';
}