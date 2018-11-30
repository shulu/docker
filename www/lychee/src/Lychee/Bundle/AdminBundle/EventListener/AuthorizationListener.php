<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14-8-26
 * Time: 下午3:13
 */

namespace Lychee\Bundle\AdminBundle\EventListener;


use Lychee\Bundle\AdminBundle\Components\Foundation\AdminBundleUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AuthorizationListener {

    protected $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function onKernelController(FilterControllerEvent $event) {
        $controller = $event->getController();
        if (is_array($controller)) {
            $controller = $controller[0];
        }
        $classInfo = new \ReflectionClass($controller);
        if (
            false !== strpos($classInfo->getNamespaceName(), 'AdminBundle') &&
            $classInfo->hasMethod('getTitle')
        ) {
            if (
                false === $this->container->get('security.context')->isGranted(
                    AdminBundleUtility::getRoleName($controller)
                )
            ) {
                throw new AccessDeniedException();
            }
        }
    }
} 