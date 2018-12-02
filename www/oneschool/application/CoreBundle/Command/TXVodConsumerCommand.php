<?php
namespace Lychee\Bundle\CoreBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class TXVodConsumerCommand extends ContainerAwareCommand {
    private $output;

    protected function configure() {
        $this->setName('lychee:txvod:consumer')
            ->setDescription('Control specific consumer')
            ->setHelp(<<<Help
This command will pull the specific tencent video event.

Help
            )
            ->addArgument('event', InputArgument::REQUIRED, 'the event name in tencent video.')
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->output = $output;
        $event = $input->getArgument('event');
        $this->consume($input, $output, $event);
    }

    private function consume(InputInterface $input, OutputInterface $output, $event) {
        $action = 'handle'.$event;
        if (!is_callable(array($this, $action))) {
            return $output->writeln('can not resolve this action '. $action);
        }
        $txVodApi = $this->getContainer()->get('lychee.component.video');
        $env = $this->getContainer()->get('kernel')->getEnvironment();
        if ('dev'==$env) {
            $txVodApi->enableDebug();
        }
        $txVodApi->pullEvent($event, function ($eventCtx) use ($action) {
            $this->$action($eventCtx);
        });
    }

    private function post() {
        return $this->getContainer()->get('lychee.module.post');
    }

    private function handleProcedureStateChanged($eventCtx) {

        if (empty($eventCtx['data']['aiReview'])) {
            return false;
        }

        if (!in_array('Porn', $eventCtx['data']['aiReview']['riskType'])) {
            return false;
        }

        $fileId = $eventCtx['data']['fileId'];
        $postService = $this->post();
        $postId = $postService->fetchIdBySVId($fileId);
        if (empty($postId)) {
            throw new \Exception($postId.'帖子不存在');
        }

        $postService->deletePronShortVideo($postId);
    }


    private function handleFileDeleted($eventCtx) {
        $fileLists = $eventCtx['data']['fileInfo'];
        $postService = $this->post();

        foreach ($fileLists as $item) {
            $fileId = $item['fileId'];
            $postId = $postService->fetchIdBySVId($fileId);
            if (empty($postId)) {
                throw new \Exception($postId.'帖子不存在');
            }
            $postService->delete($postId);
        }

    }

}