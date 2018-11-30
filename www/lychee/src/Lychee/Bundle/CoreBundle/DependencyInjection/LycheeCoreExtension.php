<?php

namespace Lychee\Bundle\CoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\BadMethodCallException;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class LycheeCoreExtension extends Extension {
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container) {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $this->configUpload($config, $container);
        $this->configSensitiveWords($config, $container);
    }

    private function configUpload($config, ContainerBuilder $container) {
        $host = $config['image_domain'];
        $uploadPath = $config['image_upload_path'];

        $container->setParameter('lychee.module.upload.image_domain', $host);
        $uploadDef = new Definition('Lychee\Module\Upload\UploadService');
        $uploadDef->setArguments(array(
            $uploadPath,
            $host,
            new Reference('router'),
            'lychee_image_upload'
        ));
        $container->setDefinition('lychee.module.upload', $uploadDef);
    }

    private function configSensitiveWords($config, ContainerBuilder $container) {
        if (isset($config['sensitive_words']) === false) {
            return;
        }
        $sensitiveWordsFilePath = $config['sensitive_words'];
        $encoding = $config['sensitive_words_encoding'];
        $checkerDef = new Definition('Lychee\Component\Security\SensitiveWordChecker\TrieSensitiveWordChecker');
        $checkerDef->setArguments(array(
            $sensitiveWordsFilePath,
            '%kernel.cache_dir%',
            $encoding
        ));
        $checkerDef->addTag('kernel.cache_warmer');
        $container->setDefinition('lychee_core.sensitive_word_checker', $checkerDef);

        $validatorDef = new Definition('Lychee\Bundle\CoreBundle\Validator\Constraints\NotSensitiveWordValidator');
        $validatorDef->setArguments(array(
            new Reference('lychee_core.sensitive_word_checker')
        ));
        $validatorDef->addTag('validator.constraint_validator', array('alias' => 'lychee_core.validator.not_sensitive_word'));
        $container->setDefinition('lychee_core.validator.not_sensitive_word', $validatorDef);
    }

}
