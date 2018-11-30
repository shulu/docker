<?php
namespace Lychee\Bundle\CoreBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class TXVodConsumerScheduleCommand extends ContainerAwareCommand {
    protected function configure() {
        $this->setName('lychee:txvod-consumer:schedule')
            ->setDescription('Control specific consumer')
            ->setHelp(<<<Help
This command will control the specific consumer scheduler.
Actions:
- start     start consumer
- stop      wait and stop consumer
- restart   restart consumer

Help
            )
            ->addArgument('action', InputArgument::REQUIRED, 'action to perform')
            ->addArgument('consumer', InputArgument::REQUIRED, 'consumer name, the event name in tencent video.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $action = $input->getArgument('action') . 'Action';
        $consumer = $input->getArgument('consumer');

        if (is_callable(array($this, $action))) {
            $this->$action($input, $output, $consumer);
        } else {
            $output->writeln('can not resolve this action '. $action);
        }
    }

    public function startAction(InputInterface $input, OutputInterface $output, $consumer) {
        $output->writeln("Starting consumer [{$consumer}].");

        $commandLine = $this->getStartConsumerCommand($consumer);
        $process = new Process(
            $commandLine, getcwd(), null, null, null
        );
        $process->run(function($type, $data) use ($output) {
            $output->writeln($data);
        });
        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Command exit with code:'.$process->getExitCode());
        }
    }

    public function stopAction(InputInterface $input, OutputInterface $output, $consumer) {
        if ($this->isRunningConsumer($consumer) === false) {
            $output->writeln('consumer is not running');
            return;
        }

        $process = new Process($this->getStopConsumerCommand($consumer), getcwd(), null, null, null);
        $process->run(function($type, $data) use ($output) {
            $output->writeln($data);
        });
        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Command exit with code:'.$process->getExitCode());
        }
    }

    public function restartAction(InputInterface $input, OutputInterface $output, $consumer) {
        if ($this->isRunningConsumer($consumer) === false) {
            $this->startAction($input, $output, $consumer);
        } else {
            $process = new Process($this->getRestartConsumerCommand($consumer), getcwd(), null, null, null);
            $process->run(function ($type, $data) use ($output) {
                $output->writeln($data);
            });
            if (!$process->isSuccessful()) {
                throw new \RuntimeException('Command exit with code:' . $process->getExitCode());
            }
        }
    }

    public function testAction(InputInterface $input, OutputInterface $output, $consumer) {

    }

    private function isRunningConsumer($consumer) {
        $process = new Process('forever list', getcwd(), null, null, null);
        $outputs = array();
        $process->run(function($type, $data) use (&$outputs) {
            $outputs[] = $data;
        });
        $allOutput = implode('', $outputs);

        $allOutput = preg_replace('/\\x1b\\[[0-9;]*m/', '', $allOutput);
        if (preg_match('/\ndata:\s+\\[\d+\\]\s+txvod-consumer-'.$consumer.'\s/', $allOutput)) {
            return true;
        } else {
            return false;
        }
    }

    private function getStartConsumerCommand($consumer) {
        $consolePath = $this->getContainer()->getParameter('kernel.root_dir').'/console';
        $commandName = 'lychee:txvod:consumer';
        $consumerName = escapeshellarg($consumer);
        $varPath = $this->getVarDirPath($consumer);
        $env = $this->getContainer()->get('kernel')->getEnvironment();
        $noDebug='';
        if ('prod'==$env) {
            $noDebug='--no-debug';
        }
        return implode(' ', array(
            'forever start', "-p $varPath", "-l {$consumerName}.log",
            "-o $varPath/{$consumerName}-out.log", "-e $varPath/{$consumerName}-err.log",
            "--pidFile=txvod-consumer-{$consumerName}.pid", '--append', '--killSignal=SIGTERM',
            '--minUptime=1000', '--spinSleepTime=1000', "--uid=\"txvod-consumer-{$consumer}\"",
            '-c "php"', $consolePath, $commandName, $consumerName, '-e='.$env, $noDebug
        ));
    }

    private function getStopConsumerCommand($consumer) {
        $consumerName = escapeshellarg($consumer);
        return "forever stop txvod-consumer-{$consumerName}";
    }

    private function getRestartConsumerCommand($consumer) {
        $consumerName = escapeshellarg($consumer);
        return "forever restart txvod-consumer-{$consumerName}";
    }

    private function getVarDirPath($consumer) {
        $rootDir = $this->getContainer()->getParameter('kernel.root_dir');
        $pathPieces = array($rootDir, '..', '..', 'var', 'txvod_consumer', $consumer);
        $varDirPath = implode(DIRECTORY_SEPARATOR, $pathPieces);
        if (file_exists($varDirPath) === false) {
            mkdir($varDirPath, 0777, true);
        }
        return $varDirPath;
    }

    private function getPidFilePath($consumer) {
        $varDir = $this->getVarDirPath($consumer);
        return $varDir . DIRECTORY_SEPARATOR . $consumer . '.pid';
    }

    private function getLogFilePath($consumer) {
        $varDir = $this->getVarDirPath($consumer);
        return $varDir . DIRECTORY_SEPARATOR . $consumer . '.log';
    }
} 