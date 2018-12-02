<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14-7-9
 * Time: 上午11:50
 */

namespace Lychee\Bundle\AdminBundle\Command;


use Lychee\Module\Topic\Entity\TopicGroup;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class InitTopicGroupRelsCommand extends ContainerAwareCommand
{
    private $output;
    private $input;

    /**
     *
     */
    protected function configure() {
        $this->setName('lychee-admin:topic:init-group-rels')
            ->setDescription('初始化次元分类');
    }

    private function getGroupConfigs()
    {

        $groups = [];
        $groups[] = ['自拍', [25150, 25220,35024,41951]];
        $groups[] = ['宅向', [25076,54703,54723,25354,36094,27115,25168,25384,25159,25211,28874,25115]];
        $groups[] = ['美图', [27925,29661,25511,25497,28711,25473,46853,32129,34016,29579,34753,31167]];
        $groups[] = ['大触', [25362,26454,29759,31168,33787,54237,25386,25430]];
        $groups[] = ['三次元', [32872,35409,25109,25181,25158,31747,31825,32636,30965,32352,30727]];
        $groups[] = ['影音', [25935,54728,54699,34316]];
        $groups[] = ['游戏', [50194,53634,35601,48064,48019,26082,25183,54639]];

        return $groups;
    }

    private function getConnection()
    {
        static $conn = null;
        if ($conn) {
            return $conn;
        }
        $conn = $this->doctrine()->getManager()->getConnection();
        return $conn;
    }

    private function resetGroupRels($groupId, $topicIds)
    {
        $topicService = $this->topic();
        $conn = $this->getConnection();
        $sql = "select topic_id from topic_group_rel where group_id = ?";
        $r = $conn->executeQuery($sql, [$groupId])->fetchAll(\PDO::FETCH_ASSOC);
        $oldTopicIds = [];
        foreach ($r as $item) {
            $oldTopicIds[] = $item['topic_id'];
        }

        $deletedTopicIds = array_diff($oldTopicIds, $topicIds);
        $addTopicIds = array_diff($topicIds, $oldTopicIds);


        if ($deletedTopicIds) {

            $sql = "delete from topic_group_rel where group_id=%s and topic_id in (%s)";
            $sql = sprintf($sql, $groupId, implode(',', $deletedTopicIds));
            $conn->executeUpdate($sql);
        }

        if ($addTopicIds) {
            foreach ($addTopicIds as $topicId) {
                $topicService->topicAddGroup($topicId, $groupId);
            }
        }

    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->output = $output;
        $this->input = $input;

        $groups = $this->getGroupConfigs();

        $topicService = $this->topic();

        $groupRepo = $this->doctrine()->getManager()
            ->getRepository(TopicGroup::class);

        $weight = count($groups);

        foreach ($groups as $key => $item) {
            list($groupName, $topicIds) = $item;
            $group = $groupRepo->findOneBy(['name'=>$groupName]);
            if ($group) {
                $groupId = $group->id;
                $topicService->updateGroup($group->id, $groupName, $weight);
            } else {
                $group = $topicService->addGroup($groupName, $weight);
                $groupId = $group->id;
            }

            $weight--;
            try {
                $this->resetGroupRels($groupId, $topicIds);
            } catch (\Exception $e) {
                $this->output->writeln($e->__toString());
            }
        }

        $this->output->writeln('Done');

    }

    private function doctrine() {
        return  $this->getContainer()->get('doctrine');
    }

    private function topic() {
        return  $this->getContainer()->get('lychee.module.topic');
    }

}