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

class MoveUGSVCoverStoreCommand extends ContainerAwareCommand {
    use ModuleAwareTrait;

    protected function configure() {
        $this->setName('lychee-admin:move-ugsv-cover-store')
            ->setDescription('将短视频封面迁移到七牛云.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $cursorId = 0;
        $sql = <<<'SQL'
SELECT post_id FROM ugsv_post WHERE post_id > ? ORDER BY post_id ASC LIMIT 1000
SQL;
        $postModule = $this->post();

        $conn = $this->getContainer()->get('doctrine')->getManager()->getConnection();

        $sum = 0;

        $output->writeln('开始处理...');

        while (true) {
            $statement = $conn->executeQuery($sql, array($cursorId));
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($result as $item) {
                $output->writeln('迁移 '.$item['post_id']);
                $postModule->moveShortVideoCoverStoreById($item['post_id']);
            }

            $presum = count($result);

            $output->writeln('处理了'.$presum.'条记录');

            $sum += $presum;

            if ($presum < 1000) {
                break;
            }

            $cursorId = $result[$presum - 1]['post_id'];

        }

        $output->writeln('...处理完毕，已处理记录数：'.$sum);

    }
}
