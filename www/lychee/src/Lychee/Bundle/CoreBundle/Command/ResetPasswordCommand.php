<?php
namespace Lychee\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ResetPasswordCommand extends ContainerAwareCommand {
    use ModuleAwareTrait;

    protected function configure() {
        $this
            ->setName('lychee:utilily:reset-password')
            ->setDefinition(array())
            ->setDescription('reset users password')
            ->setHelp(<<<EOT
This command will reset users password.

EOT
            )->addArgument('user_id', InputArgument::REQUIRED, 'user id')
            ->addArgument('password', InputArgument::REQUIRED, 'password')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $userId = intval($input->getArgument('user_id'));
        $password = $input->getArgument('password');
        if ($userId == 0) {
            return;
        }
        if (strlen($password) < 6 || strlen($password) > 20) {
            $output->writeln("<error>invalid password</error>");
        }
        $this->authentication()->updatePasswordForUser($userId, $password);
    }

}