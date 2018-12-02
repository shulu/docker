<?php
namespace Lychee\Bundle\ApiBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Lychee\Bundle\ApiBundle\Error\CodeGenerator\Loader\XmlLoader;
use Lychee\Bundle\ApiBundle\Error\CodeGenerator\Generator\ClassGenerator;
use Lychee\Bundle\ApiBundle\Error\CodeGenerator\ErrorSection;

class GenerateErrorCodeCommand extends Command {
    protected function configure() {
        $this
            ->setName('lychee:develop:generate-error')
            ->setDefinition(array())
            ->setDescription('Generate the lychee server error code and message methods')
            ->setHelp(<<<EOT
This command will scan the source code dir recursively, and process any "error.php" file it found.

EOT
            )->addArgument('namespace', InputArgument::OPTIONAL, 'override the error namespace');
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output) {
        $namespace = $input->getArgument('namespace') ?: 'Lychee\\Bundle\\ApiBundle\\Error';

        $rootPath = $this->getDefaultSourceRootPath();
        $sections = XmlLoader::create()->load($rootPath);
        $generator = new ClassGenerator();
        foreach ($sections as $section) {
            /** @var ErrorSection $section */
            $generator->generate($section, $rootPath, $namespace);
            list($oldNamespace, $className) = $this->splitNamespaceAndClassName($section->getClass());
            $output->writeln('Generate ' . $namespace . '\\' . $className);
        }
    }

    private function splitNamespaceAndClassName($class) {
        $lastBackslashPos = strrpos($class, '\\');

        return ($lastBackslashPos === false || $lastBackslashPos === 0) ?
            array(null, $class) :
            array(substr($class, 0, $lastBackslashPos), substr($class, $lastBackslashPos + 1));
    }

    private function getDefaultSourceRootPath() {
        if (DIRECTORY_SEPARATOR === '/') {
            $pattern = '/(?<=\\/src\\/).*?$/';
        } else {
            $pattern = '/(?<='.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.').*?$/';
        }

        return preg_replace($pattern, '', __DIR__);
    }
} 