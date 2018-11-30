<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 9/14/15
 * Time: 11:40 AM
 */

namespace Lychee\Bundle\AdminBundle\Command;


use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QiniuStorageUploadCommand extends ContainerAwareCommand {
    use ModuleAwareTrait;

    protected function configure() {
        $this->setName('lychee-admin:qiniu:upload')
            ->setDescription('七牛云存储 -- 上传文件')
            ->addArgument('key', InputArgument::REQUIRED)
            ->addArgument('path', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $key = $input->getArgument('key');
        $path = $input->getArgument('path');
        $storage = $this->storage();
        try {
            $r = $storage->stat($key);
            $output->writeln('文件 ('.$key.') 已存在，上传时间：'.date('Y-m-d H:i:s', sprintf('%0.10s', $r['putTime'])));
            return;
        } catch (\Lychee\Component\Storage\StorageException $e) {}
        $storage->setPutFileSizeLimit(100 * 1024 * 1024);
        $url = $storage->put($path, $key);
        $output->writeln($url);
    }
}
