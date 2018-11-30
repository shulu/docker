<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14-8-15
 * Time: 下午3:41
 */

namespace Lychee\Bundle\AdminBundle\Command;


use Doctrine\ORM\EntityManager;
use Lychee\Bundle\AdminBundle\Components\Foundation\AdminBundleUtility;
use Lychee\Bundle\AdminBundle\Entity\Role;
use Lychee\Bundle\CoreBundle\ContainerAwareTrait;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Foundation\CursorableIterator\AbstractCursorableIterator;
use Lychee\Component\Foundation\CursorableIterator\QueryCursorableIterator;
use Lychee\Module\Analysis\AnalysisType;
use Lychee\Module\Analysis\DailyCountTrait;
use Lychee\Module\Like\Entity\PostLike;
use Lychee\Module\Like\Entity\CommentLike;
use Lychee\Module\Analysis\Entity\AdminDailyAnalysis;
use Lychee\Module\Relation\Entity\UserFollowing;
use Proxies\__CG__\Lychee\Bundle\CoreBundle\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GenerateRolesCommand
 * @package Lychee\Bundle\AdminBundle\Command
 */
class AnalysisCommand extends ContainerAwareCommand {
    use DailyCountTrait;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     *
     */
    protected function configure() {
        $this->setName('lychee-admin:analysis')
            ->setDescription('Analysis Data.')
            ->addArgument('action', InputArgument::REQUIRED, "What do you want to do? Use 'list' to get commands.");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $action = $input->getArgument('action');
        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
        $analysisCommandClass = new \ReflectionClass($this);
        $methods = $analysisCommandClass->getMethods(\ReflectionMethod::IS_FINAL);
        $actions = [];
        foreach ($methods as $method) {
            $actions[] = substr($method->getName(), 0, strpos($method->getName(), 'Action'));
        }
        if ('list' === $action) {
            foreach ($actions as $action) {
                $output->writeln($action);
            }
        } elseif (in_array($action, $actions)) {
            $method = $action . 'Action';
            $this->$method($output);
        } else {
            $output->writeln('Unknown argument: ' . $action);
            $output->writeln("Use 'list' to get available arguments.");
        }
    }

    /**
     * @param OutputInterface $output
     */
    final private function topicAction(OutputInterface $output)
    {
        $iterator = $this->topic()->iterateTopic();
        $iterator->setStep(500);
        $this->analysis($output, $iterator, AnalysisType::TOPIC);
    }

    /**
     * @param OutputInterface $output
     */
    final private function userAction(OutputInterface $output)
    {
        $iterator = $this->account()->iterateAccount();
        $iterator->setStep(1000);
        $this->analysis($output, $iterator, AnalysisType::USER);
    }

    /**
     * @param OutputInterface $output
     */
    final private function postAction(OutputInterface $output)
    {
        $iterator = $this->post()->iteratePost();
        $iterator->setStep(5000);
        $this->analysis($output, $iterator, AnalysisType::POST);
    }

    /**
     * @param OutputInterface $output
     */
    final private function characterCommentAction(OutputInterface $output)
    {
        $iterator = $this->comment()->iterateCommentOnlyCharacter();
        $iterator->setStep(5000);
        $this->analysis($output, $iterator, AnalysisType::CHARACTER_COMMENT);
    }

    /**
     * @param OutputInterface $output
     */
    final private function imageCommentAction(OutputInterface $output)
    {
        $iterator = $this->comment()->iterateCommentWithImage();
        $iterator->setStep(5000);
        $this->analysis($output, $iterator, AnalysisType::IMAGE_COMMENT);
    }

    final private function contentContributionAction(OutputInterface $output)
    {
        // 统计开始至今，每天贡献内容的用户数和总用户数
        $date = new \DateTime('2013-11-17');
        $this->contentContribution($this->entityManager, $date, $output);
    }

    /**
     * @param OutputInterface $output
     * @param AbstractCursorableIterator $iterator
     * @param $analysisType
     * @param string $datetimeProperty
     */
    private function analysis(OutputInterface $output, AbstractCursorableIterator $iterator, $analysisType, $datetimeProperty = 'createTime')
    {
        $output->writeln('Start to analysis ' . $analysisType);
        $this->dailyAnalysis($this->entityManager, $iterator, $analysisType, 0, $output, $datetimeProperty);
        $output->writeln('Done.');
    }
}