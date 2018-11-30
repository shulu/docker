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

class ExportTopicAnalysisCommand extends ContainerAwareCommand {
    use ModuleAwareTrait;

    private $conn = null;

    protected function configure() {
        $this->setName('lychee-admin:export-topic-analysis')
            ->setDescription('Export topic analysis by views.')
            ->addArgument('startDate', InputArgument::REQUIRED, 'Export a week data from the start date.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $startDate = new \DateTime($input->getArgument('startDate'));
        $endDate = clone $startDate;
        $endDate = $endDate->add(new \DateInterval('P1W'));
        $this->conn = $this->getContainer()->get('Doctrine')->getConnection();
        $topicCount = 500;
        $visitStmt = $this->conn->prepare(
            "SELECT topic_id, COUNT(id) topic_visit_count FROM topic_visitor_log
            WHERE create_time>=:start AND create_time<:end
            GROUP BY topic_id
            ORDER BY topic_visit_count DESC
            LIMIT $topicCount"
        );
        $visitStmt->bindValue(':start', $startDate->format('Y-m-d'));
        $visitStmt->bindValue(':end', $endDate->format('Y-m-d'));
        printf("Visit Log Query.\n");
        $visitStmt->execute();
        $visitResult = array_reduce($visitStmt->fetchAll(), function($result, $t) {
            $result[$t['topic_id']] = $t['topic_visit_count'];
            return $result;
        });
        $topicIds = array_keys($visitResult);
        printf("Topic Info Query.\n");
        $topicsInfo = $this->fetchTopicsInfo($topicIds);
        $managerIds = array_reduce($topicsInfo, function($result, $item) {
            if ($item['user_id']) {
                $result[] = $item['user_id'];
            }
            return $result;
        });
        $lastLogin = $this->lastLogin($managerIds);
        printf("Topic Post Query.\n");
        $topicsPost = $this->topicPost($topicIds, [$startDate, $endDate]);
        printf("Topic Follow Query.\n");
        $topicsFollow = $this->topicFollow($topicIds, [$startDate, $endDate]);
        printf("Topic Comment Query.\n");
        $topicsComment = $this->topicComment($topicIds, [$startDate, $endDate]);
        printf("Start to generate csv.\n");
        $fp = fopen($startDate->format('Y-m-d') . '.csv', 'w');
        fputcsv($fp, ['次元ID', '次元名', '领主ID', '领主昵称', '领主最后登录时间', '周发帖量', '周访问量', '周入驻数', '周评论数', '总入驻数']);
        foreach ($visitResult as $tid => $row) {
            if (isset($topicsInfo[$tid])) {
                $topicTitle = $topicsInfo[$tid]['title'];
                $topicManagerId = $topicsInfo[$tid]['user_id'];
                $topicManager = $topicsInfo[$tid]['nickname'];
                $topicFollowerCount = $topicsInfo[$tid]['follower_count'];
            } else {
                $topicTitle = $topicManagerId = $topicManager = $topicFollowerCount = $topicsInfo[$tid]['follower_count'] = '';
            }
            fputcsv($fp, [
                $tid,
                $topicTitle,
                $topicManagerId,
                $topicManager,
                isset($lastLogin[$topicManagerId])? date('Y-m-d H:i:s', $lastLogin[$topicManagerId]):'',
                isset($topicsPost[$tid])? $topicsPost[$tid]:'',
                $row,
                isset($topicsFollow[$tid])? $topicsFollow[$tid]:'',
                isset($topicsComment[$tid])? $topicsComment[$tid]:'',
                $topicFollowerCount
            ]);
        }
        fclose($fp);
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

    private function topicPost($topicIds, $week) {
        $idsString = implode(',', $topicIds);
        $stmt = $this->conn->prepare(
            "SELECT tp.topic_id, COUNT(tp.post_id) post_count FROM topic_post tp
            JOIN post p ON p.id=tp.post_id
            WHERE tp.topic_id IN ($idsString) AND p.create_time>=:start AND p.create_time<:end
            GROUP BY tp.topic_id"
        );
        $stmt->bindValue(':start', $week[0]->format('Y-m-d'));
        $stmt->bindValue(':end', $week[1]->format('Y-m-d'));
        $stmt->execute();
        return array_reduce($stmt->fetchAll(), function($result, $r) {
            $result[$r['topic_id']] = $r['post_count'];
            return $result;
        });
    }

    private function topicFollow($topicIds, $week) {
        $idsString = implode(',', $topicIds);
        $stmt = $this->conn->prepare(
            "SELECT topic_id, COUNT(user_id) follow_count FROM topic_user_following
            WHERE topic_id IN ($idsString) AND create_time>=:start AND create_time<:end AND state=1
            GROUP BY topic_id"
        );
        $stmt->bindValue(':start', $week[0]->format('Y-m-d'));
        $stmt->bindValue(':end', $week[1]->format('Y-m-d'));
        $stmt->execute();
        return array_reduce($stmt->fetchAll(), function($result, $r) {
            $result[$r['topic_id']] = $r['follow_count'];
            return $result;
        });
    }

    private function topicComment($topicIds, $week) {
        $topicIdsStr = implode(',', $topicIds);
        $stmt = $this->conn->prepare(
            "SELECT tp.topic_id, COUNT(pc.comment_id) comment_count FROM topic_post tp
            JOIN post_comment pc ON pc.post_id=tp.post_id
            JOIN comment c ON c.id=pc.comment_id
            WHERE tp.topic_id IN($topicIdsStr) AND c.create_time>=:start AND c.create_time<:end
            GROUP BY tp.topic_id"
        );
        $stmt->bindValue(':start', $week[0]->format('Y-m-d'));
        $stmt->bindValue(':end', $week[1]->format('Y-m-d'));
        $stmt->execute();
        return array_reduce($stmt->fetchAll(), function($result, $item) {
            $result[$item['topic_id']] = $item['comment_count'];
            return $result;
        });
    }
}
