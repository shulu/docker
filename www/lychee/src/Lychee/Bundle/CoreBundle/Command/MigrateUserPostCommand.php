<?php
namespace Lychee\Bundle\CoreBundle\Command;

use Lychee\Component\Foundation\ArrayUtility;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;

class MigrateUserPostCommand extends ContainerAwareCommand {
    use ModuleAwareTrait;

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
            ->setName('lychee:migration:user-post')
            ->setDefinition(array())
            ->setDescription('Migration user\'s posts data ')
            ->setHelp(<<<EOT
This command will migration old user's posts data

EOT
            )->addArgument('start_id', InputArgument::OPTIONAL, 'start topic id', 0)
        ;
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
                $this->migrateUserPost($output, $id);
                $error = false;
            } catch (\Exception $e) {
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

    private function migrateUserPost(OutputInterface $output, $userId) {
        $cursor = 0;
        do {
            $postIds = $this->post()->fetchIdsByAuthorId($userId, $cursor, 200, $nextCursor);
            if (count($postIds) > 0) {
                $postIdTopicIdMap = $this->getPostTopicIds($postIds);
//                $st = microtime(true);
                $this->updateUserPostTopics($userId, $postIdTopicIdMap);
//                $et = microtime(true);
//                $output->writeln(sprintf('update: %d, time: %f', count($postIdTopicIdMap), $et - $st));
            }
            $cursor = $nextCursor;
        } while($cursor != 0);
    }

    private function getPostTopicIds($postIds) {
        $sql = 'SELECT id, topic_id FROM post WHERE id IN ('. implode(',', $postIds) .')';
        $stat = $this->conn->executeQuery($sql);
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        $result = array();
        foreach ($rows as $row) {
            $result[$row['id']] = $row['topic_id'];
        }
        return $result;
    }

    private function updateUserPostTopics($userId, $map) {
        $sql = 'UPDATE user_post SET topic_id = (CASE post_id';
        foreach ($map as $postId => $topicId) {
            $sql .= ' WHEN '.$postId.' THEN '.($topicId == null ? 0 : $topicId);
        }
        $sql .= ' END) WHERE user_id = '.$userId.' AND post_id IN('.implode(',', array_keys($map)).')';
        $this->conn->executeUpdate($sql);
    }

    private function updateUserPostTopics2($userId, $map) {
        static $stat = null;
        if (is_null($stat)) {
            $stat = $this->conn->prepare('UPDATE user_post SET topic_id = ? WHERE user_id = ? AND post_id = ?');
        }
        foreach ($map as $postId => $topicId) {
            $stat->bindParam(1, $topicId, \PDO::PARAM_INT);
            $stat->bindParam(2, $userId, \PDO::PARAM_INT);
            $stat->bindParam(3, $postId, \PDO::PARAM_INT);
            $stat->execute();
        }
    }

    /**
     *
     * @param int $previousId
     * @return int|null
     */
    private function nextId($previousId) {
        $sql = 'SELECT user_id FROM user_post WHERE user_id > ? ORDER BY user_id ASC LIMIT 1';
        $statement = $this->connection()->executeQuery($sql, array($previousId));
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        if (count($result) == 0) {
            return null;
        } else {
            return $result[0]['user_id'];
        }
    }

}