<?php
namespace Lychee\Bundle\CoreBundle\Command;

use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Module\Account\AccountRecover;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RecoverPostCommand extends ContainerAwareCommand {
    use ModuleAwareTrait;

    protected function configure() {
        $this
            ->setName('lychee:utilily:recover-post')
            ->setDefinition(array())
            ->setDescription('Revocer post data')
            ->setHelp(<<<EOT
This command will recover special post.

EOT
            )->addArgument('postId', InputArgument::REQUIRED, 'post id')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $postId = intval($input->getArgument('postId'));
        $this->post()->undelete($postId);
    }
}