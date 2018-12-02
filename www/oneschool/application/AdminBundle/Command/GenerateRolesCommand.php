<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14-8-15
 * Time: 下午3:41
 */

namespace Lychee\Bundle\AdminBundle\Command;


use Lychee\Bundle\AdminBundle\Components\Foundation\AdminBundleUtility;
use Lychee\Bundle\AdminBundle\Entity\Role;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GenerateRolesCommand
 * @package Lychee\Bundle\AdminBundle\Command
 */
class GenerateRolesCommand extends ContainerAwareCommand {

    /**
     *
     */
    protected function configure() {
        $this->setName('lychee-admin:roles:generate')
            ->setDescription('Generate Roles');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $classInfo = new \ReflectionClass($this);
        $namespace = $classInfo->getNamespaceName();
        $controllerNamespace = substr($namespace, 0, strrpos($namespace, "\\") + 1) . 'Controller';
        $rootDir = $this->getContainer()->get('kernel')->getRootDir();
        $curDir = $rootDir;
        $srcDir = null;
        do {
            $parentDir = substr($curDir, 0, strrpos($curDir, DIRECTORY_SEPARATOR));
            $files = scandir($parentDir);
            $srcIsFound = false;
            foreach ($files as $file) {
                $srcDir = $parentDir . DIRECTORY_SEPARATOR . $file;
                if ($file === 'src' && is_dir($srcDir)) {
                    $srcIsFound = true;
                    break;
                }
            }
            if (true === $srcIsFound) {
                break;
            }
            $curDir = $parentDir;
        } while (is_dir($curDir));

        $roles = array();
        if ($srcDir) {
            $controllerDir = $srcDir . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR, $controllerNamespace);
            $filesInControllerDir = scandir($controllerDir);
            foreach ($filesInControllerDir as $file) {
                $controllerName = substr($file, 0, strrpos($file, '.'));
                $controller = $controllerNamespace . "\\" . $controllerName;
                try {
                    $function = new \ReflectionClass($controller);
                } catch (\Exception $e) {
                    continue;
                }
                if ($function->hasMethod('getTitle') && false === $function->isAbstract()) {
                    $controllerInstance = new $controller();
                    $controllerInstance->setContainer($this->getContainer());
                    $adminModuleName = $controllerInstance->getTitle();
                    $role = AdminBundleUtility::getRoleName($controller);
                    $roles[$role] = array($adminModuleName, $this->getRouterName($controller));
                }
            }
        }

        $em = $this->getContainer()->get('doctrine')->getManager();
        $roleRepo = $em->getRepository('LycheeAdminBundle:Role');
        foreach ($roles as $role => $roleArray) {
            $existRole = $roleRepo->findBy(array(
                'role' => $role
            ));
            if (!$existRole) {
                $roleEntity = new Role();
                $roleEntity->role = $role;
                $roleEntity->name = $roleArray[0];
                $roleEntity->routeName = $roleArray[1];
                $em->persist($roleEntity);
                $output->writeln(sprintf("%s(%s) Generated", $role, $roleArray[0]));
            }
        }
        $em->flush();
        $output->writeln('Done');
    }

    /**
     * @param $fullControllerName
     * @return string
     */
    private function getRouterName($fullControllerName) {
        $array = explode("\\", $fullControllerName);
        if ('Bundle' === $array[1]) {
            unset($array[1]);
        }
        if ('Controller' === $array[3]) {
            unset($array[3]);
        }
        $routerComponents = array();
        foreach ($array as $element) {
            if (preg_match('/\w+Controller$/', $element)) {
                $underlineStyle = strtolower(str_replace('Controller', '', $element));
            } else {
                $underlineStyle = AdminBundleUtility::setCamelCaseToUnderline($element);
                $underlineStyle = str_replace('_bundle', '', $underlineStyle);
                $underlineStyle = str_replace('_controller', '', $underlineStyle);
            }
            $routerComponents[] = $underlineStyle;
        }

        return implode('_', $routerComponents) . '_index';
    }

}