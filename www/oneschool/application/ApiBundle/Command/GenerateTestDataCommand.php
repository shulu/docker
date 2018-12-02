<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14-7-9
 * Time: 上午11:50
 */

namespace Lychee\Bundle\ApiBundle\Command;


use Lychee\Module\Post\PostParameter;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class GenerateTestDataCommand extends ContainerAwareCommand
{

    private $accountModule;

    private $authenticationModule;

    private $postModule;

    private $topicModule;

    private $relationModule;

    private $doctrine;

    private $userIds;

    private $sampleSize = 5;

    private $defaultPassword = '123456';

    private $testImageUrl = 'http://images.sports.cn/Image/2014/07/09/0757188041.jpg';

    // ====

    private $output;
    private $input;


    /**
     *
     */
    protected function configure() {
        $this->setName('lychee:develop:gen-testdata')
            ->setDescription('Generate Test Data.')
            ->addArgument('action', InputArgument::REQUIRED, "What do you want to do? Use 'list' to get commands.");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {

        $this->output = $output;
        $this->input = $input;

        $action = $input->getArgument('action');
        $class = new \ReflectionClass($this);
        $methods = $class->getMethods(\ReflectionMethod::IS_FINAL);
        $actions = array();

        foreach ($methods as $method) {

            $methodName = substr($method->getName(), 0, strpos($method->getName(), 'Action'));
            $actions[$methodName] = array(
                'doc'=>$method->getDocComment()
            );
        }
        if ('list' === $action) {
            foreach ($actions as $action => $item) {
                $output->writeln($action);
            }
        } elseif (isset($actions[$action])) {
            $method = $action . 'Action';
            $this->$method();
        } else {
            $output->writeln('Unknown argument: ' . $action);
            $output->writeln("Use 'list' to get available arguments.");
        }
    }


    /**
     *  造基本数据
     */
    final private function baseAction() {
        $this->accountAction();
        $this->topicAction();
        $this->postAction();
        $this->recPostsAction();
        $this->topicCategorysAction();
    }

    /**
     * 造用户
     */
    final private function accountAction() {

        $ciyoUser = $this->account()->fetchOne($this->account()->getCiyuanjiangID());
        if (empty($ciyoUser)) {
            $sql='ALTER TABLE `user` AUTO_INCREMENT = 31720;';
            $conn=$this->doctrine()->getManager()->getConnection();
            $conn->executeUpdate($sql);
        }

        $count = 100;
        $avatarUrl='http://qn.ciyocon.com/upload/Frou_AT5acNQM1XdKeHEP-eMg74e';
        $nicknameGenerator = new \Lychee\Module\Account\NicknameGenerator();
        $accountModule = $this->account();
        $this->output->writeln('Generating Accounts...');
        for ($i = 0; $i < $count; $i++) {
            $nickname = $nicknameGenerator->generate();
            $email = 'Test_' . $nickname . '@lychee.com';
            try {
                $user = $accountModule->createWithEmail($email, $nickname);
            } catch (\Exception $e) {
                continue;
            }
            $this->authentication()->createPasswordForUser($user->id, $this->defaultPassword);
            $user->avatarUrl = $avatarUrl;
            $accountModule->updateInfo($user->id, $user->gender, $user->avatarUrl, $user->signature);
            $user = null;
        }
        $this->output->writeln('Done.');
    }

    /**
     * 造短视频背景音乐
     */
    final private function ugsvBGMAction() {
        $count = 100;
        $this->output->writeln('Generating UGSV BGM...');
        $bgm = $this->getContainer()->get('lychee.module.ugsv.bgm');
        $p = new \Lychee\Module\UGSV\BGMParameter();

        $singerNames= array('岸本齐史', '初音未来', '宫崎骏');

        for ($i = 0; $i < $count; $i++) {
            $p->name='GARNiDELiA '.uniqid();
            $p->cover='http://qn.ciyocon.com/upload/Frou_AT5acNQM1XdKeHEP-eMg74e';
            $p->src='http://qn.ciyocon.com/ugsvbgm/Flqs5VGcyjYcr9WtYaaZlQiLE3kf';
            $p->singerName = $singerNames[mt_rand(0, 2)];
            $p->duration = mt_rand(10, 600);
            $p->size = $p->duration*1024*6;
            $bgm->create($p);
        }
        $this->output->writeln('Done.');
    }

    /**
     * 造短视频白名单
     */
    final private function ugsvWhiteListAction() {
        $this->output->writeln('Generating UGSV White List...');

        $userIds = range(1, 100);
        $whitelist = $this->getContainer()->get('lychee.module.ugsv.whitelist');
        $whitelist->create($userIds);

        $this->output->writeln('Done.');
    }

    /**
     * 造次元
     */
    final private function topicAction() {
        $this->output->writeln('Generating Topic...');

        $defs = "25150	 自拍
25076	 Cosplay
32872	 处CP
27925	 高清壁纸
29661	 表情包
25935	 电影
35409	 情话w
25511	 二次元美图
25109	 二货日常
25181	 搞笑
25158	 次元酱的万事屋
54703	 一起来跳宅舞
25497	 二次元头像
28711	 动漫情侣头像
31747	 让我听听你的牢骚吧
25362	 手绘
50194	 异次元通讯
53634	 今晚吃鸡
25473	 头像
54723	 出道吧！次元偶像！
35601	 王者荣耀
31825	 单身狗抱团取暖
46853	 动漫头像、壁纸
32636	 今日搭配
30965	 唯美句子
48064	 re.从零开始的异世界生活
25220	 手控
48019	 守望先锋OVERWATCH
25354	 古风
32352	 句子迷
36094	 新番动漫资讯
27115	 双马尾研究社
32129	 魔性表情包
34016	 P站美图推荐社
30727	 今天你脱团了吗？
25168	 初音未来
26082	 LOL
29579	 动漫表情
34753	 动漫情头
54699	 复仇者联盟
25384	 声优
35024	 眼控的世界
31167	 半身头像
25183	 Minecraft
26454	 手写
25115	 LoveLive
25159	 手办
41951	 眼镜收藏
54639	 恋与制作人
34316	 日剧同好会
54728	 千面英雄
29759	 化妆教程
25211	 东方Project
31168	 灵魂手绘
33787	 手作一家亲
54237	 料理次元
25386	 橡皮章
28874	 日文歌同好会
25430	 板绘
53057	 次元大厅";

        $list = explode("\n", $defs);
        $userId = 1;
        $topic = $this->topic();
        $count = 100;
        $topic->increaseUserCreatingQuota($userId, $count);

        $conn = $this->doctrine()->getManager()->getConnection();
        foreach ($list as $item) {
            list($topicId, $topicName) = explode("\t", $item);
            $topicName = trim($topicName, chr(194).chr(160)." \n\t");
            $r = $topic->fetchOneByTitle($topicName);
            if ($r && $r->id!=$topicId) {
                $r->title .= date('|YmdHis');
                $topic->update($r);
            }

            $r = $topic->fetchOne($topicId);
            if ($r && $r->title!==$topicName) {
                $r->title = $topicName;
                $topic->update($r);
                continue;
            } elseif ($r) {
                continue;
            }



            $sql = <<<'SQL'
INSERT INTO topic 
(id, title, summary, description, create_time, index_image_url, cover_image_url, creator_id, manager_id)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
SQL;

            try {

                $conn->executeUpdate($sql, [
                    $topicId,
                    $topicName,
                    $topicName .' summary',
                    $topicName .' description',
                    date('Y-m-d H:i:s'),
                    'http://qn.ciyocon.com/upload/Ftukf1ei0TZCsBboXvZAQWQaXShz',
                    'http://qn.ciyocon.com/upload/Ftukf1ei0TZCsBboXvZAQWQaXShz',
                    $userId,
                    $userId
                ]);

            } catch (\Exception $e) {
                echo $e->__toString();
                continue;
            }

        }

        $this->output->writeln('Done.');
    }

    /**
     * 造关注关系
     */
    final private function followingAction()
    {
        $this->output->writeln('Generating Follow...');
        $userId = 21;
        $otherUsers = $this->getUsers(100, $userId);
        foreach ($otherUsers as $other) {
            $this->relation()->makeUserFollowAnother($userId, $other);
        }
        $this->output->writeln('Done.');
    }

    /**
     * 造短视频帖子
     */
    final private function ugsvPostAction() {
        $this->output->writeln('Generating UGSV Post...');

        $count = 10;
        $postModule = $this->post();
        $likeModule = $this->getContainer()->get('lychee.module.like');


        $userId = 2;
        $topicId = 53157;
        $followedBefore = 0;
        $this->following()->follow($userId, $topicId, $followedBefore);

        for ($i=0; $i < $count; $i++) { 

            $parameter = new \Lychee\Module\Post\PostParameter();

            $content = date('Y-m-d H:i:s').' 发。';

            $parameter->setTopicId($topicId);
            $parameter->setContent($content);

            $videoUrl = $audioUrl = $siteUrl = '';
            $imageUrl = 'http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/e85209d07447398155567673211/7447398155567673213.jpg';
            $videoUrl = 'http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/e85209d07447398155567673211/n0wAX6iwoZ0A.mp4';

            $annotation = array();
            $annotation['video_cover_width'] = 360;
            $annotation['video_cover_height'] = 636;
            $annotation['video_cover'] = $imageUrl;
            $annotation = json_encode($annotation);

            $parameter->setAuthorId($userId);
            $parameter->setAuthorLevel(1);
            $parameter->setResource($imageUrl, $videoUrl, $audioUrl, $siteUrl);
            $parameter->setAnnotation($annotation);
            $parameter->setSvId('7447398155565827538');
            $parameter->setBgmId(1);

            $type = 7;
            $parameter->setType($type);
            $post = $postModule->create($parameter);

            $likedBefore = 0;
            $likedCount = mt_rand(6, 20);
            for ($j=1; $j < $likedCount; $j++) {
                $postId = $post->id;
                $likeModule->likePost($j, $postId, $likedBefore);
            }

        }
        $this->output->writeln('Done.');
    }



    /**
     * 造帖子
     */
    final private function postAction() {
        $this->output->writeln('Generating Post...');

        $count = 10;
        $postModule = $this->post();
        $likeModule = $this->getContainer()->get('lychee.module.like');

        $topics = $this->getRecTopicIds();
        $userIds = $this->getUsers();


        for ($i=0; $i < $count; $i++) { 

            $parameter = new \Lychee\Module\Post\PostParameter();

            $content = date('Y-m-d H:i:s').' 发。';

            $topicId = $topics[mt_rand(0, count($topics)-1)];
            $userId = $userIds[mt_rand(0, count($userIds)-1)];
            
            $parameter->setTopicId($topicId);
            $parameter->setContent($content);

            $videoUrl = $audioUrl = $siteUrl = '';
            $imageUrl = 'http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/e85209d07447398155567673211/7447398155567673213.jpg';

            $annotation = array();
            $annotation = json_encode($annotation);

            $followedBefore = 0;
            $this->following()->follow($userId, $topicId, $followedBefore);
            $parameter->setAuthorId($userId);
            $parameter->setAuthorLevel(1);
            $parameter->setResource($imageUrl, $videoUrl, $audioUrl, $siteUrl);
            $parameter->setAnnotation($annotation);

            $type = \Lychee\Bundle\CoreBundle\Entity\Post::TYPE_NORMAL;
            $parameter->setType($type);
            $post = $postModule->create($parameter);

            $likedBefore = 0;
            for ($j=1; $j < 11; $j++) { 
                $postId = $post->id;
                $likeModule->likePost($j, $postId, $likedBefore);
            }

        }
        $this->output->writeln('Done.');
    }


    /**
     * 造涉黄帖子
     */
    final private function postPornAction() {
        $this->output->writeln('Generating Porn Post...');

        $postModule = $this->post();
        $topics = $this->getRecTopicIds();
        $userIds = $this->getUsers();

        $parameter = new \Lychee\Module\Post\PostParameter();

        $content = date('Y-m-d H:i:s').' 发。';

        $topicId = $topics[mt_rand(0, count($topics)-1)];
        $userId = $userIds[mt_rand(0, count($userIds)-1)];

        $parameter->setTopicId($topicId);
        $parameter->setContent($content);

        $videoUrl = $audioUrl = $siteUrl = '';
        $imageUrl = 'http://qn.ciyocon.com/upload/FtjwddKZgUQyME816SicY1kKZwJU';
        $imageUrl = 'http://qn.ciyocon.com/upload/Fpus4ccUNTAuvQrNKlzlJAjEAxmQ';

        $annotation = array();
        $annotation['multi_photos'] = [$imageUrl];
        $annotation = json_encode($annotation);

        $followedBefore = 0;
        $this->following()->follow($userId, $topicId, $followedBefore);
        $parameter->setAuthorId($userId);
        $parameter->setAuthorLevel(1);
        $parameter->setResource($imageUrl, $videoUrl, $audioUrl, $siteUrl);
        $parameter->setAnnotation($annotation);

        $type = \Lychee\Bundle\CoreBundle\Entity\Post::TYPE_NORMAL;
        $parameter->setType($type);
        $post = $postModule->create($parameter);


        $this->output->writeln('Done.');
    }

    /**
     * 生成次元分类
     */
    final private function topicCategorysAction() {
        $categorys= '偶像,兴趣,动漫,影视,活动,游戏,生活,社团,逗比';
        $categorys = explode(',', $categorys);
        $module = $this->getContainer()->get('lychee.module.topic.category');
        foreach ($categorys as $category) {
            try {
                $module->addCategory($category);
            } catch (\Exception $e) {}
        }
    }


    /**
     * 造合法登录用户
     */
    final private function loginAccountAction() {
        $areaCode = 86;
        $phoneList = [];
        $phoneList[] = '12345678901';
        $phoneList[] = '15812431454';
        $phoneList[] = '18520395535';
        $phoneList[] = '18800001701';
        $phoneList[] = '13822152700';
        $phoneList[] = '18565160432';
        $phoneList[] = '13118849715';

        $password = 123456;
        foreach ($phoneList as $phone) {

            try {
                $user = $this->account()->createWithPhone($phone, $areaCode);
            } catch (\Lychee\Module\Account\Exception\PhoneDuplicateException $e) {
                $user = $this->account()->fetchOneByPhone($areaCode, $phone);
            }
            try {
                $this->authentication()->createPasswordForUser($user->id, $password);
            } catch (\Exception $e) {
                echo $e->getMessage()."\r\n";
            }

            $this->collectCycles();
        }
    }

    /**
     * 新增vip用户
     */
    final private function vipAccountAction() {
        $areaCode = 86;
        $phoneList = [];
        $phoneList[] = '12345678901';
        $phoneList[] = '15812431454';
        $phoneList[] = '18520395535';
        $phoneList[] = '18800001701';
        $phoneList[] = '13822152700';
        $phoneList[] = '18565160432';
        $phoneList[] = '13118849715';

        $password = 123456;
        $accountService = $this->account();

        $em = $this->doctrine()->getManager();
        foreach ($phoneList as $phone) {

            if (!$em->isOpen()) {
                $em = $this->doctrine()->resetManager();
            }

            try {
                echo '根据手机号'.$phone."查询用户\r\n";
                $user = $this->account()->fetchOneByPhone($areaCode, $phone);
                if (empty($user)) {
                    echo $areaCode.'+'.$phone."不存在用户\r\n";
                    continue;
                }
                echo '处理'.$user->id."\r\n";
                // $accountService->addVip($user->id);
                $vip = new \Lychee\Module\Account\Entity\UserVip();
                $vip->userId = $user->id;
                $vip->certificationText = '';
                $em->persist($vip);
                $em->flush();
            } catch (\Doctrine\ORM\ORMException $e) {
                echo $e->getMessage();
                echo "\r\n";
            } catch (\Exception $e) {
                echo $e->getMessage();
                echo "\r\n";
            }
        }

    }


    private function collectCycles() {
        $this->doctrine()->getManager()->clear();
        $this->doctrine()->getManager()->getConnection()->getConfiguration()->setSQLLogger(null);
        gc_collect_cycles();
    }

    private function doctrine() {
        return  $this->getContainer()->get('doctrine');
    }


    private function following() {
        return $this->getContainer()->get('lychee.module.topic.following');
    }

    private function post() {
        return $this->getContainer()->get('lychee.module.post');
    }

    private function topic() {
        return $this->getContainer()->get('lychee.module.topic');
    }

    private function account() {
        return $this->getContainer()->get('lychee.module.account');
    }
    private function authentication() {
        return $this->getContainer()->get('lychee.module.authentication');
    }
    private function relation() {
        return $this->getContainer()->get('lychee.module.relation');
    }




//     public function execute(InputInterface $input, OutputInterface $output)
//     {
//         $this->accountModule = $this->getContainer()->get('lychee.module.account');
//         $this->authenticationModule = $this->getContainer()->get('lychee.module.authentication');
//         $this->postModule = $this->getContainer()->get('lychee.module.post');
//         $this->topicModule = $this->getContainer()->get('lychee.module.topic');
//         $this->relationModule = $this->getContainer()->get('lychee.module.relation');
//         $this->doctrine = $this->getContainer()->get('doctrine');

//         $this->output = $output;
// //        $this->generateAccount();
// //        gc_collect_cycles();
// //        $this->generateTopics();
// //        gc_collect_cycles();
// //        $this->generatePosts();
// //        gc_collect_cycles();
// //        $this->generateRelation();
//         $this->genRelations();
//     }

//     private function generateAccount()
//     {
//         $this->output->writeln('Generating Accounts...');
//         for ($i = 0; $i < $this->sampleSize; $i++) {
//             $nickname = uniqid();
//             $email = 'Test_' . $nickname . '@lychee.com';
//             try {
//                 $user = $this->accountModule->create($email, $nickname);
//             } catch (\Exception $e) {
//                 continue;
//             }
//             $this->authenticationModule->createPasswordForUser($user->id, $this->defaultPassword);
// //            $this->output->writeln(sprintf("Memory Usage: %s", memory_get_usage()));
//             $user = null;
//         }
//     }

    // private function postAction()
    // {
    //     $this->output->writeln('Generating Posts...');
    //     $this->userIds = $this->getUsers();
    //     $topics = $this->getTopicIds();
    //     foreach ($this->userIds as $userId) {
    //         for ($i = 0; $i < $this->sampleSize; $i++) {
    //             $randomNum = mt_rand(0, 999);
    //             if ($randomNum >= 400) {
    //                 $topic = array_rand($topics);
    //                 $this->generatePostPerUser($userId, $topic);
    //             } else {
    //                 $this->generatePostPerUser($userId);
    //             }
    //         }
    //         gc_collect_cycles();
    //     }

    //     $this->output->writeln('Done.');
    // }

    private function generateRelation()
    {
        $this->output->writeln('Generating Relations...');
        foreach ($this->userIds as $userA) {
            foreach ($this->userIds as $userB) {
                if ($userA !== $userB && !$this->relationModule->isUserFollowingAnother($userA, $userB)) {
                    $this->relationModule->makeUserFollowAnother($userA, $userB);
                }
            }
        }
    }


    private function getRecTopicIds()
    {
        $topics = $this->doctrine()->getRepository(\Lychee\Module\Recommendation\Entity\RecommendableTopic::class)->findAll();
        $topicIds = [];
        foreach ($topics as $topic) {
            $topicIds[] = $topic->topicId;
        }

        return $topicIds;
    }

    private function getTopicIds()
    {
        $topics = $this->doctrine()->getRepository(\Lychee\Module\Topic\Entity\Topic::class)->findAll();
        $topicIds = [];
        foreach ($topics as $topic) {
            $topicIds[] = $topic->id;
        }

        return $topicIds;
    }

    private function generatePostPerUser($userId, $topicId = null)
    {
        $postParam = new PostParameter();
        $postParam->setAuthorId($userId);
        $postParam->setTopicId($topicId);
        $postParam->setContent(uniqid(), uniqid());
        $postParam->setType(1);
        $postParam->setResource('http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/e85209d07447398155567673211/7447398155567673213.jpg');
        $post = $this->post()->create($postParam);
        $this->doctrine()->getManager()->clear();
        $this->doctrine()->getManager()->getConnection()->getConfiguration()->setSQLLogger(null);
        gc_collect_cycles();
        $this->output->writeln('Memory Usage: ' . memory_get_usage());

        return $post;
    }

    private function getUsers($max = 10000, $except = null)
    {
        $userRepository = $this->doctrine()->getRepository('LycheeCoreBundle:User');
        $queryBuilder = $userRepository->createQueryBuilder('u')
            ->select('u.id')
            ->setMaxResults($max);
        if (is_numeric($except)) {
            $queryBuilder->where('u.id <> :uid')
                ->setParameter('uid', $except);
        }
        $queryBuilder = $queryBuilder->getQuery();
        $result = $queryBuilder->getResult();
        $userIds = array();
        foreach ($result as $user) {
            $userIds[] = $user['id'];
        }

        return $userIds;
    }
    // 推荐帖子
    final private function recPostsAction()
    {
        $this->output->writeln('Generating Recommendation Posts...');
        $postids = array();
        $sql = "SELECT id FROM post where deleted=0 order by id desc limit 10";
        $stat = $this->doctrine()->getManager()->getConnection()->executeQuery($sql);
        $r = $stat->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($r as $item) {
            $postids[] = $item['id'];
        }
        $groupService = $this->getContainer()->get('lychee.module.recommendation.group_posts');
        $groupId=4;
        try {
            $groupService->addPostIdsToGroup($groupId, $postids);
        } catch (\Exception $e) {}
        $this->output->writeln('Done.');

    }
}
