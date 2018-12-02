<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14-7-9
 * Time: 上午11:50
 */

namespace Lychee\Bundle\AdminBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class QueryPhoneCaptchaCommand extends ContainerAwareCommand
{
    /**
     *
     */
    protected function configure() {
        $this->setName('lychee-admin:phone-captcha:query')
            ->setDescription('发送手机验证码')
            ->addArgument('areaCode', InputArgument::REQUIRED, "手机区号")
            ->addArgument('phone', InputArgument::REQUIRED, "手机号");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $areaCode = $input->getArgument('areaCode');
        $phone = $input->getArgument('phone');
        $sql = 'SELECT * FROM phone_code WHERE area_code = ? AND phone = ?';
        $conn = $this->getContainer()->get('Doctrine')->getConnection();
        $statement = $conn->executeQuery($sql, [$areaCode, $phone]);
        $r = $statement->fetch(\PDO::FETCH_ASSOC);
        $msg = sprintf("验证码 ：%s \r\n创建时间 ：%s \r\n是否过期：%s \r\n",
            $r['code'],
            date('Y-m-d H:i:s', $r['create_time']),
            time()-$r['create_time']>60?'是':'否');
        $output->writeln($msg);
    }

}