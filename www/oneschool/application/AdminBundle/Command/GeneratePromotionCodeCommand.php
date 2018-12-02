<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14-8-15
 * Time: 下午3:41
 */

namespace Lychee\Bundle\AdminBundle\Command;


use Lychee\Bundle\AdminBundle\Components\Foundation\AdminBundleUtility;
use Lychee\Bundle\AdminBundle\Entity\Role;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GenerateRolesCommand
 * @package Lychee\Bundle\AdminBundle\Command
 */
class GeneratePromotionCodeCommand extends ContainerAwareCommand {

	use ModuleAwareTrait;
    /**
     *
     */
    protected function configure() {
        $this->setName('extramessage:promotioncode:pre_gen')
	        ->addArgument('count')
            ->setDescription('Pre Generate PromotionCode');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
	    $count = intval($input->getArgument('count'));
	    $count = $count < 0 ? 0 : $count;
	    $count = $count < 100000 ? $count : 100000;

	    $this->extraMessageService()->preGeneratePromotionCode($count);

	    $output->writeln($count);
        $output->writeln('Done');
    }

}