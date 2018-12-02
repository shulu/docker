<?php

namespace Lychee\Bundle\AdminBundle\Command;


use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Lychee\Component\Database\DoctrineUtility;
use Lychee\Module\Topic\Entity\Topic;
use Lychee\Module\Topic\TopicService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TopicsVisitCommand extends ContainerAwareCommand {

    use ModuleAwareTrait;

	/**
	 * @var \PDO
	 */
	private $conn;

    protected function configure() {
        $this->setName('lychee-admin:topics-visit')
            ->setDescription('指定次元的日访问统计')
	        ->addOption('topics', null, InputOption::VALUE_REQUIRED, '需要统计的次元ID, 使用逗号分隔')
            ->addOption('start-date', null, InputOption::VALUE_REQUIRED)
            ->addOption('end-date', null, InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
    	$startDate = $input->getOption('start-date');
	    $endDate = $input->getOption('end-date');
	    $interval = new \DateInterval('P1D');
	    $startDateObj = new \DateTimeImmutable($startDate);
	    $endDateObj = (new \DateTimeImmutable($endDate))->add($interval);
	    $dateRange = new \DatePeriod($startDateObj, $interval, $endDateObj);
	    $topicIds = $input->getOption('topics');
	    $this->conn = $this->getContainer()->get('Doctrine')->getConnection();

	    $output->writeln('=============Daily Topics Visit=============');
	    $fp = fopen(sprintf("daily_topics_visit_%s_%s.csv", $startDate, $endDate), 'w');
	    $this->dailyTopicsVisit($fp, $dateRange);
	    fclose($fp);
	    $output->writeln('=============JingXuan Topics Visit=============');
	    $fp = fopen(sprintf("jingxuan_topics_visit_%s_%s.csv", $startDate, $endDate), 'w');
	    $this->specialTopicsVisit(["25362","25076","50194","25150","29661","27925","32872","25176","35601","25158","25168","25115","48064","25511","35409","25220","25087","25218","28154","35024","25211","25617","28711","25497","48019","28420","30653","26082","25183","25077","26504","32636","25354","25337","25518","46607","31252","35360","25109","31747","25097","25430","25159","31168","26454","25386","25473","31167","34753","46853","32433","29579","32129","27557","26847","25531","27115","41951","29759","40333","30965","31825","30727","31893","43123","28447","34016","28823","33787","32352","34316","36094","25384","28874","25078"], $fp, $dateRange);
	    fclose($fp);
	    $output->writeln('=============Special Topics Visit=============');
	    $fp = fopen(sprintf("special_topics_visit_%s_%s.csv", $startDate, $endDate), 'w');
	    $this->specialTopicsVisit([49745, 50999, 44064, 50498, 49318, 25087, 49596, 50607, 50374, 46220], $fp, $dateRange);
	    fclose($fp);
    }

    private function dailyTopicsVisit($fp, $dateRange) {
	    fputcsv($fp, ['日期', '访问量', '唯一访问量']);
    	$stmt = $this->conn->prepare('
			SELECT tb1.v_count, tb2.uv_count
			FROM (SELECT COUNT(id) v_count FROM topic_visitor_log WHERE create_time>=:start AND create_time<:end) tb1,
				(SELECT COUNT(tb3.user_id) uv_count FROM (
						SELECT user_id FROM topic_visitor_log WHERE create_time>=:start AND create_time<:end GROUP BY user_id
					) tb3
				) tb2
		');
    	/** @var \DateTimeImmutable $date */
	    foreach ($dateRange as $date) {
	    	$dateEnd = $date->modify('+1 day');
		    $dateStr = $date->format('Y-m-d');
		    $stmt->bindValue(':start', $dateStr);
		    $stmt->bindValue(':end', $dateEnd->format('Y-m-d'));
		    $stmt->execute();
		    $result = $stmt->fetch();
		    printf("[%s] View: %s\tUniView: %s\n", $dateStr, $result['v_count'], $result['uv_count']);
		    fputcsv($fp, [$dateStr, $result['v_count'], $result['uv_count']]);
	    }
    }

    private function specialTopicsVisit($topicIds, $fp, $dateRange) {
	    fputcsv($fp, ['次元ID', '次元名称', '日期', '日新增入驻数', '日帖子新增数', '日访问量', '日唯一访问量', '帖子总数', '用户总数']);
	    $topics = $this->topic()->fetch($topicIds);
	    foreach ($topicIds as $topicId) {
	    	/** @var Topic $topic */
	    	$topic = $topics[$topicId];
	    	$topicTitle = $topic->title;
		    $topicFollowersCount = $topic->followerCount;
		    $topicPostCount = $this->getPostCount($topicId);
		    /** @var \DateTimeImmutable $date */
		    foreach ($dateRange as $date) {
		    	printf("Topic: %s\tDate: %s\n", $topicId, $date->format('Y-m-d'));
			    $dailyFollowerCount = $this->getDailyTopicFollowerCount($topicId, $date);
			    $dailyPostCount = $this->getDailyTopicPostCount($topicId, $date);
			    $dailyVisit = $this->dailyVisit($topicId, $date);
			    $dailyView = $dailyVisit['v_count'];
			    $dailyUView = $dailyVisit['uv_count'];
			    fputcsv($fp, [
			    	$topicId,
				    $topicTitle,
				    $date->format('Y-m-d'),
				    $dailyFollowerCount,
				    $dailyPostCount,
				    $dailyView,
				    $dailyUView,
				    $topicPostCount,
				    $topicFollowersCount
			    ]);
		    }
	    }
    }

    private function getPostCount($topicId) {
    	$stmt = $this->conn->prepare('SELECT COUNT(post_id) post_count FROM topic_post WHERE topic_id=:topicId');
	    $stmt->bindValue(':topicId', $topicId);
	    $stmt->execute();
	    $result = $stmt->fetch();

	    if ($result) {
	    	return (int)$result['post_count'];
	    } else {
	    	return 0;
	    }
    }

    private function getDailyTopicFollowerCount($topicId, \DateTimeImmutable $date) {
    	$dateEnd = $date->modify('+1 day');
	    $stmt = $this->conn->prepare('
			SELECT COUNT(*) follower_count FROM topic_user_following
			WHERE create_time>=:start AND create_time<:end AND topic_id=:topicId AND state<>0
			ORDER BY create_time DESC'
	    );
	    $stmt->bindValue(':start', $date->format('Y-m-d H:i:s'));
	    $stmt->bindValue(':end', $dateEnd->format('Y-m-d H:i:s'));
	    $stmt->bindValue(':topicId', $topicId);
	    $stmt->execute();
	    $result = $stmt->fetch();
	    if ($result) {
	    	return (int)$result['follower_count'];
	    } else {
	    	return 0;
	    }
    }

    private function getDailyTopicPostCount($topicId, \DateTimeImmutable $date) {
    	$dateEnd = $date->modify('+1 day');
	    $em = $this->container()->get('Doctrine')->getManager();
	    $minPostId = DoctrineUtility::getMinIdWithTime($em, Post::class, 'id', 'createTime', $date);
	    $maxPostId = DoctrineUtility::getMaxIdWithTime($em, Post::class, 'id', 'createTime', $dateEnd);
	    $stmt = $this->conn->prepare('
	        SELECT COUNT(*) post_count FROM topic_post WHERE topic_id=:topicId AND post_id>=:min AND post_id<:max
	        ORDER BY post_id
	    ');
	    $stmt->bindValue(':topicId', $topicId);
	    $stmt->bindValue(':min', $minPostId);
	    $stmt->bindValue(':max', $maxPostId);
	    $stmt->execute();
	    $result = $stmt->fetch();
	    if ($result) {
	    	return (int)$result['post_count'];
	    } else {
	    	return 0;
	    }
    }

    private function dailyVisit($topicId, \DateTimeImmutable $date) {
    	$dateEnd = $date->modify('+1 day');
	    $stmt = $this->conn->prepare('
	        SELECT tb1.v_count, tb2.uv_count
			FROM (SELECT COUNT(id) v_count FROM topic_visitor_log
					WHERE create_time>=:start AND create_time<:end AND topic_id=:topicId
				) tb1,
				(SELECT COUNT(tb3.user_id) uv_count FROM (
						SELECT user_id FROM topic_visitor_log
						WHERE create_time>=:start AND create_time<:end AND topic_id=:topicId
						GROUP BY user_id
					) tb3
				) tb2 
	    ');
	    $stmt->bindValue(':start', $date->format('Y-m-d'));
	    $stmt->bindValue(':end', $dateEnd->format('Y-m-d'));
	    $stmt->bindValue(':topicId', $topicId);
	    $stmt->execute();
	    $result = $stmt->fetch();
	    if ($result) {
	    	return $result;
	    } else {
	    	return [
	    		'v_count' => 0,
			    'uv_count' => 0,
		    ];
	    }
    }

}
