<?php
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel {
    public function registerBundles() {
        $bundles = array(
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \Symfony\Bundle\MonologBundle\MonologBundle(),
            new \Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new \Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
//            new \JMS\DiExtraBundle\JMSDiExtraBundle($this),
            new \JMS\AopBundle\JMSAopBundle(),
            new \Snc\RedisBundle\SncRedisBundle(),
            new \Lsw\MemcacheBundle\LswMemcacheBundle(),
            new \OldSound\RabbitMqBundle\OldSoundRabbitMqBundle(),
            new \FOS\ElasticaBundle\FOSElasticaBundle(),
            new \Lychee\Bundle\CoreBundle\LycheeCoreBundle(),
            new \Lychee\Bundle\AdminBundle\LycheeAdminBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new \Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new \Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new \Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            $bundles[] = new \JMS\DebuggingBundle\JMSDebuggingBundle($this);
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader) {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }

    protected function getContainerBaseClass() {
        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            return '\JMS\DebuggingBundle\DependencyInjection\TraceableContainer';
        }

        return parent::getContainerBaseClass();
    }

    public function getRootDir() {
        return parent::getRootDir();
    }

    public function getCacheDir() {
        return $this->rootDir.'/../../var/admin/cache/'.$this->environment;
    }

    public function getLogDir() {
        return $this->rootDir.'/../../var/admin/logs';
    }

}