<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14/10/30
 * Time: 下午3:41
 */

namespace Lychee\Bundle\AdminBundle;

use Lychee\Bundle\AdminBundle\Service\CustomizeContentService;
use Lychee\Bundle\AdminBundle\Service\ManagerLog\ManagerLogService;
use Lychee\Bundle\AdminBundle\Service\ManagerService;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;

trait ServiceAwareTrait
{
    use ModuleAwareTrait;

    /**
     * @return ManagerLogService
     */
    public function managerLog()
    {
        return $this->container()->get('lychee_admin.service.manager_log');
    }

    /**
     * @return ManagerService
     */
    public function manager()
    {
        return $this->container()->get('lychee_admin.service.manager');
    }

    /**
     * @return CustomizeContentService
     */
    public function customizeContentService() {
        return $this->container()->get('lychee_admin.service.customize_content');
    }
}