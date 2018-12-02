<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 5/4/16
 * Time: 4:35 PM
 */

namespace Lychee\Bundle\AdminBundle\Command;


use Lychee\Bundle\CoreBundle\ContainerAwareTrait;
use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Bundle\CoreBundle\ModuleAwareTrait;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PostAnalysisCommand extends ContainerAwareCommand {

    use ModuleAwareTrait;

    protected function configure() {
        $this->setName('lychee-admin:post-analysis')
            ->setDescription('分析指定时间段内帖子类型的占比, 及资源帖子的分享网站排名.')
            ->addArgument('start-date', InputArgument::REQUIRED, '起始日期')
            ->addArgument('end-date', InputArgument::REQUIRED, '结束日期');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        /**
         * @var \PDO $conn
         */
        $conn = $this->container()->get('Doctrine')->getConnection();
        $stmt = $conn->prepare('SELECT id FROM `post` WHERE create_time<:startDate ORDER BY id DESC LIMIT 1');
        $stmt->bindValue(':startDate', $input->getArgument('start-date'));
        if ($stmt->execute()) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $startPostId = $result['id'];
            $stmt2 = $conn->prepare('SELECT id FROM `post` WHERE create_time<:endDate ORDER BY id DESC LIMIT 1');
            $stmt2->bindValue(':endDate', $input->getArgument('end-date'));
            if ($stmt2->execute()) {
                $result = $stmt2->fetch(\PDO::FETCH_ASSOC);
                $endPostId = $result['id'];
                $stmt = $conn->prepare(
                    'SELECT id,type,content,annotation 
                    FROM `post` 
                    WHERE id>:startId AND id<=:endId AND deleted=0'
                );
                $stmt->bindValue(':startId', $startPostId);
                $stmt->bindValue(':endId', $endPostId);
                $stmt->execute();
                $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $postCount = 0;
                $postTypeCount = [
                    Post::TYPE_NORMAL => 0,
                    Post::TYPE_SCHEDULE => 0,
                    Post::TYPE_VOTING => 0,
                    Post::TYPE_GROUP_CHAT => 0,
                    Post::TYPE_RESOURCE => 0,
                ];
                $postContentCount = [
                    'word' => 0,
                    'image' => 0,
                ];
                $contentUrlCount = 0;
                $urlCount = [];
                foreach ($result as $row) {
                    $postCount += 1;
                    $postTypeCount[$row['type']] += 1;
                    switch ($row['type']) {
                        case Post::TYPE_NORMAL:
                            if (!$row['annotation']) {
                                $postContentCount['word'] += 1;
                            } else {
                                $postContentCount['image'] += 1;
                            }
                            $regex = '@(?i)\b((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))@';
                            if (preg_match($regex, $row['content'])) {
                                $contentUrlCount += 1;
                            }
                            break;
                        case Post::TYPE_RESOURCE:
                            $annotation = json_decode($row['annotation'], true);
                            if (isset($annotation['resource_link'])) {
                                $link = $annotation['resource_link'];
                                $regex = '/:\/\/([^\/]+)/';
                                if (preg_match($regex, $link, $match)) {
                                    if ($match) {
                                        $link = $match[1];
                                        if (!isset($urlCount[$link])) {
                                            $urlCount[$link] = 1;
                                        } else {
                                            $urlCount[$link] += 1;
                                        }
                                    }
                                }
                            }
                            break;
                        case Post::TYPE_GROUP_CHAT:
                            break;
                        case Post::TYPE_VOTING:
                            break;
                        case Post::TYPE_SCHEDULE:
                            break;
                    }
                }
                $output->writeln(sprintf("帖子总数: %d", $postCount));
                $output->writeln(sprintf("图文: %d\n链接: %d\n活动: %d\n投票: %d\n群聊: %d",
                    $postTypeCount[Post::TYPE_NORMAL],
                    $postTypeCount[Post::TYPE_RESOURCE],
                    $postTypeCount[Post::TYPE_SCHEDULE],
                    $postTypeCount[Post::TYPE_VOTING],
                    $postTypeCount[Post::TYPE_GROUP_CHAT]));
                $output->writeln(sprintf("图文帖: \n文字: %d(%d%%)\n图片: %d(%d%%)\n链接: %d\n",
                    $postContentCount['word'],
                    round($postContentCount['word'] / $postTypeCount[Post::TYPE_NORMAL] * 100, 2),
                    $postContentCount['image'],
                    round($postContentCount['image'] / $postTypeCount[Post::TYPE_NORMAL] * 100, 2),
                    $contentUrlCount));
                $output->writeln(sprintf("分享链接TOP5:"));
                arsort($urlCount);
                $top5Url = array_slice($urlCount, 0, 5);
                foreach ($top5Url as $url=>$count) {
                    $output->writeln(sprintf("%s\t分享次数: %d", $url, $count));
                }
            }
        }
    }
}