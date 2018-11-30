<?php
namespace Lychee\Bundle\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Lychee\Bundle\AdminBundle\Service\ManagerService;

class GenerateAdministratorCommand extends ContainerAwareCommand {
    protected function configure() {
        $this
            ->setName('lychee-admin:manager:generate')
            ->setDefinition(array())
            ->setDescription('Generate a administrator')
            ->setHelp(<<<EOT
This command will generate a system administrator.

EOT
            )
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'email')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'name')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'password')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $email = $input->getOption('email');
        $name = $input->getOption('name');
        $password = $input->getOption('password');

        $manager = $this->getManagerService()->createManager($email, $name, $password);
        $manager->roles = $this->getContainer()->get('lychee_admin.service.role')->fetchRoles();
        $this->getContainer()->get('doctrine')->getManager()->flush($manager);

        $output->writeln('Done.');
    }

    protected function interact(InputInterface $input, OutputInterface $output) {
        $output->writeln(array(
            'This command will help you generate an administrator account.'
        ));
        $email = $this->askOption('email:', $output);
        $name = $this->askOption('name:', $output);
        $password = $this->askOption('password:', $output);
        $repeatedPassword = $this->askOption('repeat password:', $output);
        if ($password != $repeatedPassword) {
            throw new \RuntimeException('difference passwords');
        }

        $input->setOption('email', $email);
        $input->setOption('name', $name);
        $input->setOption('password', $password);
    }

    private function askOption($question, $output) {
        $output->writeln($question);
        $result = fgets(STDIN, 4096);
        if (false === $result) {
            throw new \RuntimeException('Aborted');
        }
        return trim($result);
    }

    /**
     * @return ManagerService
     */
    private function getManagerService() {
        return $this->getContainer()->get('lychee_admin.service.manager');
    }
} 