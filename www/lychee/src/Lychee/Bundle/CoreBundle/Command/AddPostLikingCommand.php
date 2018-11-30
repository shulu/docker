<?php
namespace Lychee\Bundle\CoreBundle\Command;

use Lychee\Module\Operation\LikingBot\LikingBot;
use Lychee\Module\Post\PostService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class AddPostLikingCommand extends ContainerAwareCommand {
    use ModuleAwareTrait;

    protected function configure() {
        $this
            ->setName('lychee:utilily:add-post-liking')
            ->setDefinition(array())
            ->setDescription('add post like count')
            ->setHelp(<<<EOT
This command will add post like count.

EOT
            )
            ->addArgument('postId', InputArgument::REQUIRED, 'post id')
            ->addArgument('likeCount', InputArgument::REQUIRED, 'like count')
	        ->addOption('callEvent', null, InputOption::VALUE_OPTIONAL, 'call event', true)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $postId = intval($input->getArgument('postId'));
        $likeCount = intval($input->getArgument('likeCount'));
        $callEvent = $input->getOption('callEvent');
	    if (!$callEvent || 'false' === strtolower($callEvent)) {
	    	$callEvent = false;
	    }

        if ($likeCount <= 0) {
            throw new \InvalidArgumentException('invalid like count');
        }

        /** @var PostService $postService */
        $postService = $this->container()->get('lychee.module.post');

        $post = $postService->fetchOne($postId);
        if ($post == null) {
            throw new \Exception('post not exist');
        }

//        $bot = $this->container()->get('lychee.module.operation.liking_bot');
        $bot = new LikingBot($this->container()->get('doctrine'),
            $this->container()->get('lychee.module.like'), new ConsoleLogger($output));
        $bot->makeLikeToPost($postId, $likeCount, $callEvent);
    }
}