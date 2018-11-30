<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 10/10/2016
 * Time: 3:46 PM
 */

namespace Lychee\Bundle\AdminBundle\Command;


use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncRecommendableTopicCommand extends ContainerAwareCommand {

	use ModuleAwareTrait;

	protected function configure() {
		$this->setName('lychee-admin:sync-recommendable-topic')
		     ->setDescription('将精选次元同步到清水池');
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {
		$this->recommendation()->clearRecommendableTopics();
		$topicIds = $this->recommendation()->listAllRecommendationTopicIds();
		$topicIds = array_unique($topicIds);
		$this->recommendation()->addRecommendableTopics($topicIds);
	}
}