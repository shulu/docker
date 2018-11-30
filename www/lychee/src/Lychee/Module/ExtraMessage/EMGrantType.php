<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 2017/12/28
 * Time: 上午10:47
 */

namespace Lychee\Module\ExtraMessage;


interface EMGrantType
{
    const EMAIL = 'email';
    const PHONE = 'phone';
    const QQ = 'qq';
    const WEIBO = 'weibo';
    const WECHAT = 'wechat';
    const BILIBILI = 'bilibili';
    const DMZJ = 'dmzj'; // 动漫之家
    const FACEBOOK = 'facebook';
}