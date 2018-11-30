<?php

namespace Lychee\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;
use Lychee\Module\Topic\Following\TopicFollowingService;
use Lychee\Module\Post\PostService;
use Lychee\Component\Foundation\CursorableIterator\CustomizedCursorableIterator;

class CleanDeletedTopicsCommand extends ContainerAwareCommand {
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

    /**
     * @var TopicFollowingService
     */
    private $topicFollowingService;

    /**
     * @var PostService
     */
    private $postService;

    protected function configure() {
        $this
            ->setName('lychee:utilily:clean-deleted-topics')
            ->setDefinition(array())
            ->setDescription('clean deleted topics followers and posts')
            ->setHelp(<<<EOT
This command will clean deleted topics followers and posts.

EOT
            )->addArgument('start_id', InputArgument::OPTIONAL, 'start topic id', 0)
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output) {
        parent::initialize($input, $output);
        $this->topicFollowingService = $this->topicFollowing();
        $this->postService = $this->post();
    }


    protected function execute(InputInterface $input, OutputInterface $output) {
        $startId = intval($input->getArgument('start_id'));
        $this->doClean($output, $startId);
    }

    private function doClean(OutputInterface $output, $startId) {
        $id = $startId;
        $count = 0;
        $this->conn = $this->connection();
        while (($id = $this->nextId($id)) !== null) {
            try {
                $this->cleanFollowersAndPosts($output, $id);
                $error = false;
            } catch (\Exception $e) {
                var_dump($e->getMessage());
                $error = true;
            }

            if ($error) {
                $output->writeln("<error>{$id}</error>");
            }
            $this->em()->clear();
            gc_collect_cycles();
            $count += 1;
        }
    }

    private function cleanFollowersAndPosts(OutputInterface $output, $topicId) {
        $topic = $this->topic()->fetchOne($topicId);
        $output->writeln("id: $topicId, posts: {$topic->postCount}, followers: {$topic->followerCount}");
        $followerCount = $this->unfollowAllFollowers($topicId, $output);
        $postCount = $this->deleteAllPosts($topicId, $output);
        $output->writeln("");
        $output->writeln("removed posts: <info>{$postCount}</info>, followers: <info>{$followerCount}</info>");
    }

    private function unfollowAllFollowers($topicId, OutputInterface $output) {
        $iterator = $this->topicFollowingService->getTopicFollowerIterator($topicId);
        $iterator->setStep(100);
        $output->write('<info>follower</info>');
        $count = 0;
        foreach ($iterator as $userIds) {
            foreach ($userIds as $userId) {
                $this->topicFollowingService->unfollow($userId, $topicId);
                $count += 1;
                if ($count % 10 == 0) {
                    $output->write('.');
                }
            }
        }
        return $count;
    }

    private function deleteAllPosts($topicId, OutputInterface $output) {
        $iterator = new CustomizedCursorableIterator(function($cursor, $step, &$nextCursor)use($topicId){
            return $this->postService->fetchIdsByTopicId($topicId, $cursor, $step, $nextCursor);
        });
        $iterator->setStep(100);
        $output->write('<info>post</info>');
        $count = 0;
        foreach ($iterator as $postIds) {
            foreach ($postIds as $postId) {
                $this->postService->delete($postId);
                $count += 1;
                if ($count % 10 == 0) {
                    $output->write('.');
                }
            }
        }
        return $count;
    }

    /**
     *
     * @param int $previousId
     * @return int|null
     */
    private function nextId($previousId) {
        $sql = 'SELECT id FROM topic WHERE id > ? AND deleted = 1 AND (follower_count > 0 OR post_count > 0) ORDER BY id ASC LIMIT 1';
        $statement = $this->connection()->executeQuery($sql, array($previousId));
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        if (count($result) == 0) {
            return null;
        } else {
            return $result[0]['id'];
        }
    }
}