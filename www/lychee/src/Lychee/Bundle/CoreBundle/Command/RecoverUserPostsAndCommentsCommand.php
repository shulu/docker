<?php
namespace Lychee\Bundle\CoreBundle\Command;

use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Module\Account\AccountRecover;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RecoverUserPostsAndCommentsCommand extends ContainerAwareCommand {
    use ModuleAwareTrait;

    protected function configure() {
        $this
            ->setName('lychee:utilily:recover-user-posts')
            ->setDefinition(array())
            ->setDescription('Revocer user post data')
            ->setHelp(<<<EOT
This command will recover all deleted posts and comments which owned by the special user.

EOT
            )->addArgument('userId', InputArgument::REQUIRED, 'user id')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $userId = intval($input->getArgument('userId'));
        $recoverService = new AccountRecover($this->container()->get('doctrine'),
            $this->account(), $this->post(), $this->comment());
        $recoverService->recover($userId);
    }
}