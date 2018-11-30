<?php
namespace Lychee\Bundle\CoreBundle\Command;

use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Module\Account\AccountCleaner;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanUserPostsAndCommentsCommand extends ContainerAwareCommand {
    use ModuleAwareTrait;

    protected function configure() {
        $this
            ->setName('lychee:utilily:clean-user-posts')
            ->setDefinition(array())
            ->setDescription('Clean user post data')
            ->setHelp(<<<EOT
This command will delete all posts and comments which owned by the special user.

EOT
            )->addArgument('userId', InputArgument::REQUIRED, 'user id');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $userId = intval($input->getArgument('userId'));
        /** @var AccountCleaner $cleanService */
        $cleanService = $this->container()->get('lychee.module.account.posts_cleaner');
        $cleanService->cleanUserPosts($userId);
        $cleanService->cleanUserComment($userId);
    }
}