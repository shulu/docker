<?php
namespace Lychee\Component\Composer;

use Composer\Package\Package;
use Composer\Script\Event;
use Composer\Package\RootPackage;

use Symfony\Component\Process\Process;
use Sensio\Bundle\DistributionBundle\Composer\ScriptHandler as SensioScriptHandler;
use Symfony\Component\Filesystem\Filesystem;

class ScriptHandler extends SensioScriptHandler {
    /**
     * @param Event $event
     * @param Callback $updateExtra
     * @return Event
     */
    private static function duplicateEvent($event, $updateExtra) {
        $newComposer = clone ($event->getComposer());
        /** @var Package $oldPackage */
        $oldPackage = $event->getComposer()->getPackage();

        $newPackage = new RootPackage(
            $oldPackage->getName(), $oldPackage->getVersion(),
            $oldPackage->getPrettyVersion()
        );

        $packageClass = new \ReflectionClass('Composer\\Package\\RootPackage');
        $properties = array();
        foreach ($packageClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (substr_compare($method->name, 'set', 0, 3) == 0) {
                $propertyName = substr($method->name, 3);
                $properties[] = $propertyName;
            }
        }

        foreach ($properties as $propertyName) {
            $getter = self::getGetterName($packageClass, $propertyName);
            if ($getter != null) {
                $propertyValue = $oldPackage->$getter();
                if ($propertyValue !== null) {
                    $setter = 'set' . $propertyName;
                    $newPackage->$setter($propertyValue);
                }
            }
        }

        $extra = $oldPackage->getExtra();
        $newExtra = $updateExtra($extra);
        $newPackage->setExtra($newExtra);
        $newComposer->setPackage($newPackage);

        $newEvent = new Event(
            $event->getName(), $newComposer, $event->getIO(),
            $event->isDevMode(), $event->getArguments()
        );
        return $newEvent;
    }

    /**
     * @param \ReflectionClass $class
     * @param string $property
     * @return null|string
     */
    private static function getGetterName($class, $property) {
        try {
                $class->getMethod('get'.$property);
        } catch (\Exception $e) {
            try {
                $class->getMethod('is'.$property);
            } catch (\Exception $e) {
                return null;
            }
            return 'is'.$property;
        }
        return 'get'.$property;
    }

    /**
     * @param Event $event
     * @param $overrideAppDir
     * @param $action
     */
    private static function runActionForAllKernels($event, $overrideAppDir,  $action) {
        $extra = $event->getComposer()->getPackage()->getExtra();
        $apps = $extra['lychee-apps'];
        foreach ($apps as $app) {
            if ($overrideAppDir) {
                $fakeEvent = self::duplicateEvent($event, function($extra) use ($app) {
                    $extra['symfony-app-dir'] =
                        $extra['symfony-app-dir'] . DIRECTORY_SEPARATOR . $app;
                    return $extra;
                });

                $action($fakeEvent, $app);
            } else {
                $action($event, $app);
            }
        }
    }

    private static function createDirIfNonexist($dir) {
        $fileSystem = new Filesystem();
        if ($fileSystem->exists($dir) === false) {
            $fileSystem->mkdir($dir);
        }
    }

    /**
     * Builds the bootstrap file.
     *
     * The bootstrap file contains PHP file that are always needed by the application.
     * It speeds up the application bootstrapping.
     *
     * @param $event Event A instance
     */
    public static function buildBootstrap(Event $event) {
        $fakeEvent = self::duplicateEvent($event, function($extra) {
            $extra['symfony-var-dir'] =
                $extra['symfony-app-dir'].DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'var';
            self::createDirIfNonexist($extra['symfony-var-dir']);
            return $extra;
        });
        $event->getIO()->write("Attent to build bootstrap cache", true);
        SensioScriptHandler::buildBootstrap($fakeEvent);
    }

    /**
     * Clears the Symfony cache.
     *
     * @param Event $event Event A instance
     */
    public static function clearCache(Event $event) {
        self::runActionForAllKernels($event, true, function(Event $event, $appName) {
            $event->getIO()->write("Attent to clear cache for [$appName]", true);
            SensioScriptHandler::clearCache($event);
        });
    }

    /**
     * Installs the assets under the web root directory.
     *
     * For better interoperability, assets are copied instead of symlinked by default.
     *
     * Even if symlinks work on Windows, this is only true on Windows Vista and later,
     * but then, only when running the console with admin rights or when disabling the
     * strict user permission checks (which can be done on Windows 7 but not on Windows
     * Vista).
     *
     * @param $event Event A instance
     */
    public static function installAssets(Event $event) {
        self::runActionForAllKernels($event, true, function(Event $event, $appName) {
            $event->getIO()->write("Attent to install asset for [$appName]", true);
            SensioScriptHandler::installAssets($event);
        });
    }

    /**
     * Updated the requirements file.
     *
     * @param $event Event A instance
     */
    public static function installRequirementsFile(Event $event) {
        self::runActionForAllKernels($event, false, function(Event $event, $appName) {
            $event->getIO()->write("Attent to install requirements for [$appName]", true);
            SensioScriptHandler::installRequirementsFile($event);
        });
    }

    public static function dumpAssetic(Event $event) {
        self::runActionForAllKernels($event, true, function(Event $event, $appName) {
            $options = self::getOptions($event);
            $consoleDir = self::getConsoleDir($event, 'dump assetic');

            if (null === $consoleDir) {
                return;
            }

            if (self::existCommand($consoleDir, 'assetic:dump')) {
                $event->getIO()->write("Attent to dump assetic for [$appName]", true);
                static::executeCommand($event, $consoleDir, 'assetic:dump');
            }
        });
    }

    protected static function existCommand($consoleDir, $commandName) {
        $php = escapeshellarg(self::getPhp());
        $console = escapeshellarg($consoleDir.'/console');
        $cmd = 'list --raw';
        $process = new Process($php.' '.$console.' '.$cmd, null, null, null, 30);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException('An error occurred when executing the "list" command.');
        }
        $output = $process->getOutput();

        if (preg_match('/^' . preg_quote($commandName, '\s+/') . '/m', $output) == false) {
            return false;
        } else {
            return true;
        }
    }

}