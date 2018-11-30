<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14/10/30
 * Time: 下午1:48
 */

namespace Lychee\Bundle\AdminBundle\Service\ManagerLog;


class OperationType {

    const SIGN_IN = 'SignIn';

    const SIGN_OUT = 'SignOut';

    const CHANGE_PASSWORD = 'ChangePassword';

    const DELETE_POST = 'DeletePost';

    const STICKY_POST = 'sticky_post';

    const BLOCK_USER = 'block_user';

    const UNBLOCK_USER = 'unblock_user';

    const UNBLOCK_DEVICE = 'unblock_device';
} 