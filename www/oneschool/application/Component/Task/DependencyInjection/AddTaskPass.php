<?php
namespace Lychee\Component\Task\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AddTaskPass implements CompilerPassInterface {
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @api
     * @throws \InvalidArgumentException
     */
    public function process(ContainerBuilder $container) {
        $managerDefinition = $container->findDefinition('lychee.task.manager');
        $tasks = $container->findTaggedServiceIds('lychee.task');
        foreach ($tasks as $taskId => $taskAttributes) {
            $taskDefinition = $container->getDefinition($taskId);
            if (!$taskDefinition->isPublic()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must be public as task are lazy-loaded.', $taskId));
            }

            if ($taskDefinition->isAbstract()) {
                throw new \InvalidArgumentException(sprintf('The service "%s" must not be abstract as task are lazy-loaded.', $taskId));
            }
            $managerDefinition->addMethodCall('addTask', array(new Reference($taskId)));
        }
    }

} 