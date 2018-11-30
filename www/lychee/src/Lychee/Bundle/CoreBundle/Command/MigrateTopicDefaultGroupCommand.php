<?php
namespace Lychee\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;
use Lychee\Module\Topic\TopicDefaultGroupService;
use Lychee\Module\IM\GroupService;
use Lychee\Module\Topic\TopicService;

class MigrateTopicDefaultGroupCommand extends ContainerAwareCommand {
    use ModuleAwareTrait;

    /**
     * @var GroupService
     */
    private $groupService;
    /**
     * @var TopicDefaultGroupService
     */
    private $defaultGroupService;
    /**
     * @var TopicService;
     */
    private $topicService;

    /**
     * @return EntityManager
     */
    private function em() {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @return \Doctrine\DBAL\Connection
     */
    private function connection() {
        return $this->em()->getConnection();
    }

    protected function configure() {
        $this
            ->setName('lychee:migration:topic-default-group')
            ->setDefinition(array())
            ->setDescription('Create default group for old topics.')
            ->setHelp(<<<EOT
This command will create default group for old topics.

EOT
            )->addArgument('start_id', InputArgument::OPTIONAL, 'start topic id', 0)
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output) {
        parent::initialize($input, $output);
        $this->groupService = $this->container()->get('lychee.module.im.group');
        $this->defaultGroupService = $this->container()->get('lychee.module.topic.default_group');
        $this->topicService = $this->topic();
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $startId = intval($input->getArgument('start_id'));
        $this->doMigrate($output, $startId);
    }

    private function doMigrate(OutputInterface $output, $startId) {
        $id = $startId;
        $count = 0;
        $this->conn = $this->connection();
        while (($id = $this->nextId($id)) !== null) {
            try {
                $this->createDefaultGroupIfNeed($output, $id);
                $error = false;
            } catch (\Exception $e) {
                var_dump(get_class($e));
                var_dump($e->getMessage());
                $error = true;
            }

            if ($error) {
                $output->writeln("<error>{$id}</error>");
                gc_collect_cycles();
            } else {
                if ($count % 1000 == 0) {
                    $output->write("<info>{$id}</info>");
                    gc_collect_cycles();
                } else if ($count % 100 == 0) {
                    $output->write('.');
                    gc_collect_cycles();
                }
            }
            $count += 1;
        }
    }

    private function createDefaultGroupIfNeed(OutputInterface $output, $topicId) {
        $defaultGroupId = $this->defaultGroupService->getDefaultGroup($topicId);
        if ($defaultGroupId !== null) {
            return;
        }

        $topic = $this->topicService->fetchOne($topicId);
        if ($topic->creatorId > 0) {
            $group = $this->groupService->create($topic->creatorId, mb_substr($topic->title, 0, 20, 'utf8'), null, '本次元默认群聊', $topic->id);
            $this->defaultGroupService->updateDefaultGroup($topic->id, $group->id);
        }
    }

    /**
     *
     * @param int $previousId
     * @return int|null
     */
    private function nextId($previousId) {
        $sql = 'SELECT id FROM topic WHERE id > ? ORDER BY id ASC LIMIT 1';
        $statement = $this->connection()->executeQuery($sql, array($previousId));
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        if (count($result) == 0) {
            return null;
        } else {
            return $result[0]['id'];
        }
    }
}