<?php

namespace Lychee\Bundle\AdminBundle\Command;


use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TopicAnalysisCommand extends ContainerAwareCommand {

    use ModuleAwareTrait;

    /**
     * @var $conn \PDO
     */
    private $conn = null;

    protected function configure() {
        $this->setName('lychee-admin:topic-analysis')
            ->setDescription('Topic analysis.')
            ->addArgument('startDate', InputArgument::REQUIRED, 'Export a week data from the start date.')
            ->addOption('endDate', null, InputOption::VALUE_REQUIRED, 'Export till the end date.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $startDate = new \DateTime($input->getArgument('startDate'));
        $endDate = $input->getOption('endDate');
        if ($endDate) {
            $endDate = new \DateTime($endDate);
        } else {
            $endDate = clone $startDate;
            $endDate = $endDate->add(new \DateInterval('P1W'));
        }
        $output->writeln(sprintf("- Start Date:\t%s", $startDate->format('Y-m-d')));
        $output->writeln(sprintf("- End Date:\t%s", $endDate->format('Y-m-d')));
        $this->conn = $this->getContainer()->get('Doctrine')->getConnection();
        $topicCount = 300;
        $startTime = time();
        $postCount = $this->postCount($startDate, $endDate, $topicCount);
        $output->writeln(sprintf("Post Count cost:\t%ss", time() - $startTime));
        $startTime = time();
        $visitCount = $this->visitCount($startDate, $endDate, $topicCount);
        $output->writeln(sprintf("Visit Count cost:\t%ss", time() - $startTime));
        $startTime = time();
        $followCount = $this->followCount($startDate, $endDate, $topicCount);
        $output->writeln(sprintf("Follow Count cost:\t%ss", time() - $startTime));
        $startTime = time();
        $commentCount = $this->commentCount($startDate, $endDate, $topicCount);
        $output->writeln(sprintf("Comment Count cost:\t%ss", time() - $startTime));
        $topicIds = array_unique(array_merge(array_keys($postCount), array_keys($visitCount), array_keys($followCount), array_keys($commentCount)));
        $startTime = time();
        $topicsInfo = $this->fetchTopicsInfo($topicIds);
        $output->writeln(sprintf("Topics Info cost:\t%ss", time() - $startTime));
        $topicManagerIds = array_unique(array_reduce($topicsInfo, function($result, $item) {
            if ($item['user_id']) {
                $result[] = $item['user_id'];
            }
            return $result;
        }));
        $startTime = time();
        $lastLogin = $this->lastLogin($topicManagerIds);
        $output->writeln(sprintf("Check Last Login cost:\t%ss", time() - $startTime));

        $fp = fopen($startDate->format('Y-m-d') . '.csv', 'w');
        fputcsv($fp, ['次元ID', '次元名', '领主ID', '领主昵称', '领主最后登录时间', '周发帖量', '周访问量', '周入驻数', '周评论数', '总入驻数']);
        foreach ($topicIds as $tid) {
            $topicManagerId = $topicsInfo[$tid]['user_id'];
            $managerLastLogin = '';
            if ($topicManagerId && isset($lastLogin[$topicManagerId])) {
                $managerLastLogin = date('Y-m-d H:i:s', $lastLogin[$topicManagerId]);
            }
            fputcsv($fp, [
                $tid,
                $topicsInfo[$tid]['title'],
                $topicManagerId,
                $topicsInfo[$tid]['nickname'],
                $managerLastLogin,
                isset($postCount[$tid])? $postCount[$tid]:'',
                isset($visitCount[$tid])? $visitCount[$tid]:'',
                isset($followCount[$tid])? $followCount[$tid]:'',
                isset($commentCount[$tid])? $commentCount[$tid]:'',
                $topicsInfo[$tid]['follower_count']
            ]);
        }
        fclose($fp);
    }

    private function returnArray($arrayResult) {
        $result = array_reduce($arrayResult, function($result, $item) {
            $result[$item['topic_id']] = $item['group_count'];

            return $result;
        });
	    if (!is_array($result)) {
	    	return [];
	    }
	    return $result;
    }

    /**
     * 统计指定时间段内次元发帖量
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param $count
     * @return mixed
     */
    private function postCount(\DateTime $startDate, \DateTime $endDate, $count) {
        $stmt = $this->conn->prepare(
            "SELECT topic_id, COUNT(id) group_count FROM `post`
            WHERE create_time>=:start AND create_time<:end
            GROUP BY topic_id
            ORDER BY group_count DESC
            LIMIT $count"
        );
        $stmt->bindValue(':start', $startDate->format('Y-m-d'));
        $stmt->bindValue(':end', $endDate->format('Y-m-d'));
        $stmt->execute();
        return $this->returnArray($stmt->fetchAll());
    }

    /**
     * 统计指定时间内次元访问量
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param $count
     * @return mixed
     */
    private function visitCount(\DateTime $startDate, \DateTime $endDate, $count) {
        $stmt = $this->conn->prepare(
            "SELECT topic_id, COUNT(id) group_count FROM topic_visitor_log
            WHERE create_time>=:start AND create_time<:end
            GROUP BY topic_id
            ORDER BY group_count DESC
            LIMIT $count"
        );
        $stmt->bindValue(':start', $startDate->format('Y-m-d'));
        $stmt->bindValue(':end', $endDate->format('Y-m-d'));
        $stmt->execute();
        return $this->returnArray($stmt->fetchAll());
    }

    /**
     * 统计指定时间内次元入驻数
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param $count
     * @return mixed
     */
    private function followCount(\DateTime $startDate, \DateTime $endDate, $count) {
        $stmt = $this->conn->prepare(
            "SELECT topic_id, COUNT(user_id) group_count FROM topic_user_following
            WHERE create_time>=:start AND create_time<:end AND state=1
            GROUP BY topic_id
            ORDER BY group_count DESC
            LIMIT $count"
        );
        $stmt->bindValue(':start', $startDate->format('Y-m-d'));
        $stmt->bindValue(':end', $endDate->format('Y-m-d'));
        $stmt->execute();
        return $this->returnArray($stmt->fetchAll());
    }

    /**
     * 统计指定时间内次元评论数
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param $count
     * @return mixed
     */
    private function commentCount(\DateTime $startDate, \DateTime $endDate, $count) {
        $stmt = $this->conn->prepare(
            "SELECT p.topic_id, COUNT(c.id) group_count
            FROM comment c
            JOIN post p ON p.id=c.post_id
            WHERE c.create_time>=:start AND c.create_time<:end
            GROUP BY p.topic_id
            ORDER BY group_count DESC
            LIMIT $count"
        );
        $stmt->bindValue(':start', $startDate->format('Y-m-d'));
        $stmt->bindValue(':end', $endDate->format('Y-m-d'));
        $stmt->execute();
        return $this->returnArray($stmt->fetchAll());
    }

    private function lastLogin($userIds) {
        $userIdsStr = implode(',', $userIds);
        $stmt = $this->conn->prepare(
            "SELECT user_id, create_time FROM auth_token
            WHERE user_id IN ($userIdsStr)
            ORDER BY id DESC"
        );
        $stmt->execute();
        return array_reduce($stmt->fetchAll(), function($result, $item) {
            if (!isset($result[$item['user_id']])) {
                $result[$item['user_id']] = $item['create_time'];
            }
            return $result;
        });
    }

    private function fetchTopicsInfo($topicIds) {
        $stmt = $this->conn->prepare(
            "SELECT t.id topic_id, t.title, t.follower_count, u.id user_id, u.nickname FROM topic t
            LEFT OUTER JOIN user u ON u.id=t.manager_id
            WHERE t.id IN (" . implode(',', $topicIds) . ")"
        );
        $stmt->execute();
        return array_reduce($stmt->fetchAll(), function($result, $t) {
            $result[$t['topic_id']] = $t;
            return $result;
        });
    }

}
