<?php

namespace Lychee\Bundle\CoreBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Lychee\Component\Task\Command\ListTaskCommand;
use Lychee\Component\Task\Command\RunTaskCommand;
use Lychee\Component\Task\Command\ScheduleTaskCommand;
use Lychee\Component\Task\Command\SetTaskFireTimeCommand;
use Lychee\Component\Task\DependencyInjection\AddTaskPass;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class LycheeCoreBundle extends Bundle {
    
    public function build(ContainerBuilder $container) {
        parent::build($container);
        $container->addCompilerPass(new AddTaskPass());

        $kernelRoot = $container->getParameter('kernel.root_dir');
        $sourceRoot = implode(DIRECTORY_SEPARATOR, array($kernelRoot, '..', '..', 'src'));

        $emToNamespaces = [
            'core' => [
                'Lychee\Module\Account\Entity',
                'Lychee\Module\Activity\Entity',
                'Lychee\Module\Authentication\Entity',
                'Lychee\Module\Like\Entity',
                'Lychee\Module\ContentManagement\Entity',
                'Lychee\Module\Notification\Entity',
                'Lychee\Module\Recommendation\Entity',
                'Lychee\Module\Relation\Entity',
                'Lychee\Component\Task',
                'Lychee\Module\Schedule\Entity',
                'Lychee\Module\Voting\Entity',
                'Lychee\Module\Account',
                'Lychee\Module\Post\Entity',
                'Lychee\Module\Analysis\Entity',
                'Lychee\Module\Topic\Entity',
                'Lychee\Module\ContentAudit\Entity',
                'Lychee\Module\Game\Entity',
                'Lychee\Module\Promotion\Entity',
	            'Lychee\Module\Caitu\Entity',
                'Lychee\Module\UGSV\Entity',
                'Lychee\Module\CiyoActivity\Entity',
                'Lychee\Module\Robot\Entity',
                'Lychee\Module\Comment\Entity',
            ],
            'payment' => ['Lychee\Module\Payment\Entity'],
            'report' => ['Lychee\Module\Report\Entity'],
            'favorite' => ['Lychee\Module\Favorite\Entity'],
	        'live' => ['Lychee\Module\Live\Entity'],
	        'extramessage' => ['Lychee\Module\ExtraMessage\Entity'],
        ];

        $allNamespaces = call_user_func_array('array_merge', $emToNamespaces);
        $emToNamespaces['core'] = $allNamespaces;

        foreach ($emToNamespaces as $em => $namespaces) {
            $directories = array_map(function($namespace) use ($sourceRoot) {
                return $this->nameSpaceToSourceDir($namespace, $sourceRoot);
            }, $namespaces);

            $emService = sprintf( 'doctrine.entity_manager_%s', $em);
            $container->setParameter($emService, $em);
            $container->addCompilerPass(DoctrineOrmMappingsPass::createAnnotationMappingDriver($namespaces, $directories, array($emService)));
        }
    }

    private function nameSpaceToSourceDir($namespace, $sourceRoot) {
        return $sourceRoot . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $namespace);
    }

    public function registerCommands(Application $application) {
        parent::registerCommands($application);

        $application->add(new ListTaskCommand());
        $application->add(new RunTaskCommand());
        $application->add(new ScheduleTaskCommand());
        $application->add(new SetTaskFireTimeCommand());
    }


}
