<?php
namespace Lychee\Bundle\ApiBundle\Command;


use Lychee\Module\Post\PostParameter;
use Lychee\Module\UGSV\BGMParameter;
use Lychee\Module\UGSV\Entity\BGM;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HiCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('lychee:say:hi')
            ->setDefinition(array())
            ->setDescription('Test.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('hi.');
    }

}