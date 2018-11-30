<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 10/11/2016
 * Time: 6:23 PM
 */
namespace Lychee\Module\Payment;

interface PayerType {
	const DEVICE = 1;
	const USER = 2;
	const EMUSER = 3;
}