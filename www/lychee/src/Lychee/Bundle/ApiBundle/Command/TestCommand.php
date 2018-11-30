<?php
namespace Lychee\Bundle\ApiBundle\Command;


use Lychee\Module\Post\PostParameter;
use Lychee\Module\UGSV\BGMParameter;
use Lychee\Module\UGSV\Entity\BGM;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('lychee:develop:test')
            ->setDefinition(array())
            ->setDescription('Test.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->test();
    }

    public function deepArray($arr, $key) {
        $keys = explode('.', $key);
        $ret = $arr;
        foreach ($keys as $key) {
            if (!isset($ret[$key])) {
                return null;
            }
            $ret = $ret[$key];
        }
        return $ret;
    }

    public function diffFields($list1, $list2, $fieldRels) {
        $diffs = [];
        foreach ($fieldRels as $field1 => $field2) {

            $val1 = $this->deepArray($list1, $field1);
            $val2 = $this->deepArray($list2, $field2);

            if ($val1!=$val2) {
                $diffs[] = [$field1, $val1, $field2, $val2];
            }
        }
        return $diffs;
    }


    public function createHttpClient() {
        static $client = null;
        if ($client) {
            return $client;
        }

        $client = new \GuzzleHttp\Client([ 
                                            'base_uri' => 'http://api.ciyo.work.net/',
                                            'proxy'   => 'nginx.work.net:80'
                                        ]);
        return $client;
    }

    public function getRequestApi($uri, $params=[]) {
        $httpClient = $this->createHttpClient();
        $r =  $httpClient->get($uri, ['query' => $params])
                            ->getBody()
                            ->getContents();
        $r = json_decode($r, true);
        return $r;
    }

    public function postRequestApi($uri, $params=[]) {
        $httpClient = $this->createHttpClient();
        $r =  $httpClient->post($uri, ['form_params' => $params])
                            ->getBody()
                            ->getContents();
        $r = json_decode($r, true);
        return $r;
    }


    public function test() {

        try {
            $testUrl = 'http://qn.ciyocon.com/upload/FkyatuGnVqmswc6XzcpMhG5FYM-b?imageView2/2/w/120/h/99999';

            $r = parse_url($testUrl);
            var_dump($r);
            exit();
              $testUrl = 'http://qn.ciyocon.com/upload/FkyatuGnVqmswc6XzcpMhG5FYM-b?imageView2/2/w/120/h/99999';
//            $testUrl = 'http://q.ciyo.cn/ugsvcover/fcdc073163fca6e2341eaffd849b43e5';
//            $testUrl = 'http://q.ciyo.cn/ugsvcover/fcdc073163fca6e2341eaffd849b43e5?imageMogr2/thumbnail/!100x100r';
//            $testUrl = 'http://a.ciyocon.com/ugsvcover/fcdc073163fca6e2341eaffd849b43e5';
            $r = $this->getContainer()->get('lychee.component.storage');
            $r->refreshUrls([$testUrl]);
//            $r->freeze($testUrl);
//            var_dump($r->privateUrl($testUrl));
//            $r->unfreeze($testUrl);
//            $r = $this->getContainer()->get('lychee_core.sensitive_word_checker');
//            $r = $r->replaceSensitiveWords('ABCD');
//            var_dump($r);
            //             $this->testApi();
        } catch (\Exception $e) {
            echo $e->__toString();
        }
    }


    public function fixShortVideo() {
        //修复帖子内容
        $postModule = $this->getContainer()->get('lychee.module.post');
        $conn = $this->getContainer()->get('Doctrine')->getConnection();
        $query = $conn->prepare("select id from post where type=7 ");
        $query->execute();
        $list = $query->fetchAll();
        foreach ($list as $item) {
            $info = $postModule->fetchOne($item['id']);
            $annotation = json_decode($info->annotation, true);
            if (isset($annotation['video_cover_height'])
                &&$info->imageUrl==$annotation['video_cover']) {
                continue;
            }

            if (empty($annotation['video_cover_height'])) {
                list($annotation['video_cover_width'], $annotation['video_cover_height']) = getimagesize($info->imageUrl);
            }

            $annotation['video_cover'] = $info->imageUrl;
            $info->annotation = json_encode($annotation);
            $postModule->update($info);
        }

        exit('ok');
    }

    public function testApi() {


        // 测试用例列表
        $testCases = [];
        $testCases['ugsv/isopen'] = [
                '白名单判断', 
                [
                    'open'=>['存在白名单'], 
                    'no-open'=>['不存在白名单'],
                ]
            ];
        $testCases['post/create_short_video'] = [
                '发短视频帖', 
                [
                    'ok'=>['正常发帖成功'],            
                    'no-svid'=>['没有短视频文件id会失败'],      
                    'no-bgmid'=>['没有背景音乐id不影响发帖'],             
                ]
            ];
        $testCases['post/create'] = [
                '发图文帖', 
                [
                    'ok'=>['正常发帖成功'],                     
                ]
            ];

        $testCases['ugsv/video'] = [
                '获取单个短视频明细', 
                [
                    'ok'=>['发帖之后，可以正常获取到'],                     
                ]
            ];
        $testCases['ugsv/video/recs'] = [
                '获取精选短视频列表', 
                [
                    'ok'=>['发帖之后，并设为精选帖，可以正常获取到'],                     
                ]
            ];
        $testCases['ugsv/video/signature'] = [
                '获取短视频签名', 
                [
                    'ok'=>['正常获取'],         
                    'no-access_token'=>['没传access_token获取失败'],                                 
                ]
            ];
        $testCases['ugsv/video/timeline/user'] = [
                '获取指定用户的短视频列表', 
                [
                    'ok'=>['正常获取'],         
                    'no-uid'=>['没传用户id获取失败'],   
                    'turn-front'=>['往前翻结果正常'],   
                    'turn-back'=>['往后翻结果正常'],
                ]
            ];
        $testCases['ugsv/bgm/hots'] = [
                '获取背景音乐列表', 
                [
                    'ok'=>['正常获取'],         
                    'del'=>['删除后起效'],      
                    'top'=>['置顶'],                                 
                    'bottom'=>['置底'],                              
                    'switch'=>['交换位置'],
                ]
            ];


        // token
        $sql = "select user_id,access_token from auth_token limit 1";
        $conn = $this->getContainer()->get('doctrine')->getManager()->getConnection();
        $r = $conn->query($sql);
        $r = $r->fetch(\PDO::FETCH_ASSOC);
        $token = $r['access_token'];
        $userId = $r['user_id'];

        // 加vip
        $this->getContainer()->get('lychee.module.account')->addVip($userId);

        // 白名单
        $httpClient = new \GuzzleHttp\Client([
                                    'base_uri' => 'http://api.ciyo.work.net/',
                                    'proxy'   => 'nginx:80'
                                ]);
        $r = $this->getRequestApi('ugsv/isopen', ['uid' => 111111111]);
        $testRes=0;
        if (0==$r['result']) {
            $testRes=1;
        }
        $testCases['ugsv/isopen'][1]['no-open'][1] = $testRes;


        $r = $this->getRequestApi('ugsv/isopen', ['uid' => 1]);
        $testRes=0;
        if (1==$r['result']) {
            $testRes=1;
        }
        $testCases['ugsv/isopen'][1]['open'][1] = $testRes;


        // 短视频发帖
        $req=[];
        $req['app_os_version'] = '7.1.2';
        $req['client'] = 'android';
        $req['app_ver'] = '3.6.1';
        $req['access_token'] = $token;
        $req['content'] = '我使用测试工具发布的，大概是这个时间吧：'.date('Y-m-d H:i:s');
        $req['cover_width'] = '856';
        $req['cover_height'] = '480';
        $req['topic_id'] = '53057';
        $req['image_url'] = 'http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/b3b4287a7447398155675863814/7447398155675863815.jpg';
        $req['video_url'] = 'http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/b3b4287a7447398155675863814/Ylx2BBiChRYA.mp4';
        $req['sv_id'] = '7447398155675863814';
        $post = $this->postRequestApi('post/create_short_video', $req);
        $testRes = 0;
        $desc = '';

        $diffs = $this->diffFields($post, $req, [
            'topic.id' => 'topic_id',
            'content' => 'content',
            'annotation.video_cover_height' => 'cover_height',
            'annotation.video_cover_width' => 'cover_width',
            'annotation.video_cover' => 'image_url',
            'image_url' => 'image_url',
            'video_url' => 'video_url',
        ]);


        if (isset($post['id'])
        && empty($diffs)) {
            $testRes = 1;
        } else {
            $desc = "接口返回；\r\n".json_encode($post)."\r\n差异：\r\n".var_export($diffs, true);
        }
        $testCases['post/create_short_video'][1]['ok'][1] = $testRes;
        $testCases['post/create_short_video'][1]['ok'][2] = $desc;
        $testCases['post/create_short_video'][1]['no-bgmid'][1] = $testRes;
        $testCases['post/create_short_video'][1]['no-bgmid'][2] = $desc;

        $post2 = $this->getRequestApi('ugsv/video', ['id'=>$post['id']]);

        $diffs = $this->diffFields($post, $post2, [
            'topic.id' => 'topic.id',
            'content' => 'content',
            'annotation.video_cover_height' => 'annotation.video_cover_height',
            'annotation.video_cover_width' => 'annotation.video_cover_width',
            'annotation.video_cover' => 'annotation.video_cover',
            'image_url' => 'image_url',
            'video_url' => 'video_url',
        ]);

        if (isset($post['id'])
        && empty($diffs)) {
            $testRes = 1;
        } else {
            $desc = json_encode($post);
        }
        $testCases['ugsv/video'][1]['ok'][1] = $testRes;

        unset($req['sv_id']);
        $r = $this->postRequestApi('post/create_short_video', $req);
        $testRes = 0;
        $desc = '';
        if (isset($r['errors'])) {
            $testRes = 1;
        } else {
            $desc = "接口返回；\r\n".json_encode($post)."\r\n差异：\r\n".var_export($diffs, true);
        }
        $testCases['post/create_short_video'][1]['no-svid'][1] = $testRes;
        $testCases['post/create_short_video'][1]['no-svid'][2] = $desc;

        $r = $this->postRequestApi('post/create', $req);
        $testRes = 0;
        $desc = '';
        if (isset($r['id'])) {
            $testRes = 1;
        } else {
            $desc = json_encode($r);
        }
        $testCases['post/create'][1]['ok'][1] = $testRes;
        $testCases['post/create'][1]['ok'][2] = $desc;

        // 精选帖子
        $sql = "select id from post where type=7 and deleted=0 order by id desc limit 1";
        $conn = $this->getContainer()->get('doctrine')->getManager()->getConnection();
        $r = $conn->query($sql);
        $r = $r->fetch(\PDO::FETCH_ASSOC);
        $postId = $r['id'];

        try {
            $this->getContainer()->get('lychee.module.recommendation.group_posts')->addPostIdsToGroup(4, [$postId]);
        }catch (\Exception $e) {}

        $testRes = 0;
        $cursor = 0;

        do {
        
            $res = $this->getRequestApi('ugsv/video/recs', ['cursor'=>$cursor, 'count'=>100]);
            $list = $res['posts'];
            $cursor = $res['next_cursor'];
            foreach ($list as $item) {
                if ($item['id']==$postId) {
                    $testRes = 1;
                    break;
                }
            }
            if ($testRes
                ||empty($cursor)) {
                break;
            }

        } while (1);

        $testCases['ugsv/video/recs'][1]['ok'][1] = $testRes;



        // 获取签名
        $r = $this->getRequestApi('ugsv/video/signature');
        $testRes = 0;
        if (isset($r['errors'])) {
            $testRes = 1;
        }
        $testCases['ugsv/video/signature'][1]['no-access_token'][1] = $testRes;

        $r = $this->getRequestApi('ugsv/video/signature', ['access_token'=>$token]);
        $testRes = 0;
        if (!empty($r['result'])) {
            $testRes = 1;
        }
        $testCases['ugsv/video/signature'][1]['ok'][1] = $testRes;

        // 获取指定用户的短视频列表, 正常获取
        $testRes = 0;
        $cursor = 0;

        do {
        
            $res = $this->getRequestApi('ugsv/video/timeline/user', ['cursor'=>$cursor, 'count'=>100, 'uid'=>$userId]);
            $list = $res['posts'];
            $cursor = $res['next_cursor'];
            foreach ($list as $item) {
                if ($item['id']==$postId) {
                    $testRes = 1;
                    break;
                }
            }
            if ($testRes
                ||empty($cursor)) {
                break;
            }

        } while (1);
        $testCases['ugsv/video/timeline/user'][1]['ok'][1] = $testRes;

        // 获取指定用户的短视频列表, 没传用户id获取失败

        $res = $this->getRequestApi('ugsv/video/timeline/user', ['cursor'=>$cursor, 'count'=>100]);
        $testRes = 0;
        if (isset($res['errors'])) {
            $testRes = 1;
        }
        $testCases['ugsv/video/timeline/user'][1]['no-uid'][1] = $testRes;

        // 获取指定用户的短视频列表, 往前翻结果正常
        $testRes = 1;
        $cursor = 0;
        $preCursor = 0;
        $desc = '';
        do {
            $preCursor = $cursor;
            $res = $this->getRequestApi('ugsv/video/timeline/user', ['cursor'=>$cursor, 'turn'=>2, 'count'=>10, 'uid'=>$userId]);
            $list = $res['posts'];
            $cursor = $res['next_cursor'];

            $preItem = null;
            foreach ($list as $item) {
                if ($preItem && $item['id']>$preItem['id']) {
                    $testRes = 0;
                    $desc = '列表顺序不对';
                    break;
                }
                $preItem = $item;
            }

            if ($cursor&&$preCursor&&$cursor<=$preCursor) {
                    $testRes = 0;
                    $desc = '下一页的游标：'.$cursor.', 上一页的游标：'.$preCursor;
                    break;
            }

            if (empty($cursor)) {
                break;
            }

        } while (1);
        $testCases['ugsv/video/timeline/user'][1]['turn-front'][1] = $testRes;
        $testCases['ugsv/video/timeline/user'][1]['turn-front'][2] = $desc;



        // 获取指定用户的短视频列表, 往后翻结果正常
        $testRes = 1;
        $cursor = 0;
        $preCursor = 0;
        $desc = '';
        do {
            $preCursor = $cursor;
            $res = $this->getRequestApi('ugsv/video/timeline/user', ['cursor'=>$cursor, 'turn'=>1, 'count'=>10, 'uid'=>$userId]);
            $list = $res['posts'];
            $cursor = $res['next_cursor'];

            $preItem = null;
            foreach ($list as $item) {
                if ($preItem && $item['id']>$preItem['id']) {
                    $testRes = 0;
                    $desc = '列表顺序不对';
                    break;
                }
                $preItem = $item;
            }

            if ($cursor&&$preCursor&&$cursor>$preCursor) {
                    $testRes = 0;
                    $desc = '下一页的游标：'.$cursor.', 上一页的游标：'.$preCursor;
                    break;
            }

            if (empty($cursor)) {
                break;
            }

        } while (1);
        $testCases['ugsv/video/timeline/user'][1]['turn-back'][1] = $testRes;
        $testCases['ugsv/video/timeline/user'][1]['turn-back'][2] = $desc;


        // 获取背景音乐列表, 正常获取
        $res = $this->getRequestApi('ugsv/bgm/hots', ['cursor'=>0, 'count'=>10]);
        $testRes = 0;
        if ($res['list']) {
            $testRes = 1;
        }
        $testCases['ugsv/bgm/hots'][1]['ok'][1] = $testRes;

        // 获取背景音乐列表, 删除后起效
        $sql = "select id from ugsv_bgm limit 1";
        $conn = $this->getContainer()->get('doctrine')->getManager()->getConnection();
        $r = $conn->query($sql);
        $r = $r->fetch(\PDO::FETCH_ASSOC);
        $bgmId = $r['id'];

        $bgmService = $this->getContainer()->get('lychee.module.ugsv.bgm');
        $bgmService->remove($bgmId);

        $testRes = 1;
        $cursor = 0;
        $desc = '';
        do {
            $res = $this->getRequestApi('ugsv/bgm/hots', ['cursor'=>$cursor, 'count'=>100]);
            $list = $res['list'];
            $cursor = $res['next_cursor'];

            $preItem = null;
            foreach ($list as $item) {
                if ($item['id']==$bgmId) {
                    $testRes = 0;
                    $desc = '出现已删除记录';
                    break;
                }
            }

            if (empty($cursor)) {
                break;
            }

        } while (1);
        $testCases['ugsv/bgm/hots'][][1]['del'][1] = $testRes;
        $testCases['ugsv/bgm/hots'][][1]['del'][2] = $desc;

        // 获取背景音乐列表, 置顶
        $sql = "select id from ugsv_bgm where order by weight asc limit 1";
        $conn = $this->getContainer()->get('doctrine')->getManager()->getConnection();
        $r = $conn->query($sql);
        $r = $r->fetch(\PDO::FETCH_ASSOC);
        $bgmId = $r['id'];
        $bgmService->topWeight($bgmId);

        $res = $this->getRequestApi('ugsv/bgm/hots', ['cursor'=>0, 'count'=>1]);
        $list = $res['list'];
        $top = reset($list);

        $testRes = 0;
        if ($top['id']==$bgmId) {
            $testRes = 1;
        }

        $testCases['ugsv/bgm/hots'][][1]['top'][1] = $testRes;
        $testCases['ugsv/bgm/hots'][][1]['top'][2] = $desc;

        // 获取背景音乐列表, 置底
        $bgmService->bottomWeight($bgmId);
        $testRes = 0;
        $cursor = 0;
        $desc = '';
        $bottom = null;
        do {
            $res = $this->getRequestApi('ugsv/bgm/hots', ['cursor'=>$cursor, 'count'=>100]);
            $list = $res['list'];
            $cursor = $res['next_cursor'];

            $preItem = null;
            foreach ($list as $item) {
                $bottom = $item;
            }

            if (empty($cursor)) {
                break;
            }

        } while (1);

        if ($bottom&&$bottom['id']==$bgmId) {
            $testRes = 1;
        }

        $testCases['ugsv/bgm/hots'][][1]['bottom'][1] = $testRes;
        $testCases['ugsv/bgm/hots'][][1]['bottom'][2] = $desc;


        // 获取背景音乐列表, 交换位置


        // ======输出结论


        $okTips = "\033[0;32;1m √ %s, %s, %s\033[0m\r\n";
        $errTips = "\033[0;31;1m × %s, %s, %s %s\033[0m\r\n";

        foreach ($testCases as $testUri => $item) {
            list($masterTitle, $checkList) = $item;
            foreach ($checkList as $sub => $subItem) {
                if (empty($subItem[1])) {
                    $desc = isset($subItem[2])?$subItem[2]:'';
                    vprintf($errTips, [$testUri, $masterTitle, $subItem[0], $desc]);
                    continue;
                } 
               vprintf($okTips, [$testUri, $masterTitle, $subItem[0]]);
            }
        }

        exit();


        // 更新用户密码
        $areaCode = 86;
        $phone = 13825461473;

        $authentication = $this->getContainer()->get('lychee.module.authentication');
        $accountModule = $this->getContainer()->get('lychee.module.account');
        // curl -x 127.0.0.1:80 -d 'app_os_version=7.1.2&password=123456&client=android&app_ver=3.6.1&area_code=86&uuid=868027030554749&phone=13825461473&device_name=Redmi+5+Plus&channel=&sig=6766850E4F7DA91132216AA85455524F&nonce=1524724538480&' 'http://api.ciyo.work.net/auth/signin/mobile'

        $userId = 3;
        $password = 123456;
        $passwordValid = $authentication->isUserPasswordValid($userId, $password);
        var_dump($passwordValid);exit();

        $user = $accountModule->fetchOneByPhone($areaCode, $phone);
        var_dump($user);exit();

        // $authentication->createPasswordForUser($userId, $password);
        $authentication->updatePasswordForUser($userId, $password);
        exit('ok');

        //累计背景音乐使用次数
        // $postModule = $this->getContainer()->get('lychee.module.ugsv.bgm');
        // $postModule->incUseCount(1, -302);
        // exit('ok');
        $postId = 125994114526209;
        $eventDispatcher = $this->getContainer()->get('lychee.event_dispatcher_async');
        $eventDispatcher->dispatch(\Lychee\Module\Post\PostEvent::CREATE, new \Lychee\Module\Post\PostEvent($postId));
        exit('ok');
        // $postId = 126294456541185;
        // $postModule = $this->getContainer()->get('lychee.module.post');
        // $post = $postModule->fetchOne($postId);
        // $post->content="降低3000兆赫以上频段频率占用费标准。在全国范围内使用的频段，3000-4000兆赫频段由800万元/兆赫/年降为500万元/兆赫/年，4000-6000兆赫频段由800万元/兆赫/年降为300万元/兆赫/年，6000兆赫以上频段由800万元/兆赫/年降为50万元/兆赫/年。在省（自治区、直辖市）范围内使用的频段，3000-4000兆赫频段由80万元/兆赫/年降为50万元/兆赫/年，4000-6000兆赫频段由80万元/兆赫/年降为30万元/兆赫/年，6000兆赫以上频段由80万元/兆赫/年降为5万元/兆赫/年。在市（地、州）范围内使用的频段，3000-4000兆赫频段由8万元/兆赫/年降为5万元/兆赫/年，4000-6000兆赫频段由8万元/兆赫/年降为3万元/兆赫/年，6000兆赫以上频段由8万元/兆赫/年降为0.5万元/兆赫/年。";
        // $postModule->update($post);
        // print_r($post);
        // exit();

        // $userId = 3;
        // $topicId = 54689;
        // $followedBefore = 0;
        // $followingModule = $this->getContainer()->get('lychee.module.topic.following');
        // $followingModule->follow($userId, $topicId, $followedBefore);

        // exit('ok');
        // $userId = 1;
        // $signature = '吃吃吃';
        // $avatarUrl='http://qn.ciyocon.com/upload/Frou_AT5acNQM1XdKeHEP-eMg74e';
        // $gender = 1;
        // $accountModule = $this->getContainer()->get('lychee.module.account');
        // $accountModule->updateInfo($userId, $gender, $avatarUrl, $signature);
        // exit('ok');

        // $nextCursor = 0;
        // $postModule = $this->getContainer()->get('lychee.module.post');
        // $r = $postModule->fetchShortVideoUrlsByAuthorId(1, PHP_INT_MAX, 2, $nextCursor);
        // var_dump($r, $nextCursor);

        // exit('ok');

        // $s = 62;
        // echo vsprintf("%02d:%02d", array($s/60, $s%60));
        // exit();

        // echo json_encode(array('video_cover'=>'http://qn.ciyocon.com/upload/Fn3PAHZKGfQXLL3J1Oh8aXSfIyVr','video_cover_width'=>360));
        // exit();
        // $bgm = $this->getContainer()->get('lychee.module.ugsv.bgm');
        // $bgm->correctweight();
        // exit();



        // $bgm = $this->getContainer()->get('lychee.module.ugsv.bgm');
        // $r = $bgm->switchWeight(1, 2);
        // exit();

        //加入次元
        // $userId = 1;
        // $topicId = 1;
        // $this->getContainer()->get('lychee.module.topic.following')->follow($userId, $topicId, $followedBefore);

        // exit('ok');
        //新增背景音乐

        // $nicknameGenerator = new \Lychee\Module\Account\NicknameGenerator();
        // $bgm = $this->getContainer()->get('lychee.module.ugsv.bgm');
        // for ($i=0; $i < 100; $i++) { 

        //     $p = new BGMParameter();
        //     $p->name='GARNiDELiA '.uniqid();
        //     $p->cover='http://qn.ciyocon.com/upload/Frou_AT5acNQM1XdKeHEP-eMg74e';
        //     $p->src='http://qn.ciyocon.com/ugsvbgm/Flqs5VGcyjYcr9WtYaaZlQiLE3kf';
        //     $p->singerName = $nicknameGenerator->generate();
        //     $p->duration = mt_rand(10, 600);
        //     $p->size = $p->duration*1024*6;

        //     $bgm->create($p);
        // }

        // exit('ok');

        // 点赞
        // $like = $this->getContainer()->get('lychee.module.like');
        // $likedBefore = 0;
        // $postId = 125998656399361;
        // for ($i=1; $i < 10; $i++) { 
        //     $like->likePost($i, $postId, $likedBefore);
        // }

        // var_dump($likedBefore);
        // exit('ok.');

        // 新增次元

        // $topicName = '次元'.uniqid();
        // $p = new \Lychee\Module\Topic\TopicParameter();
        // $p->creatorId = 1;
        // $p->title = $topicName;
        // $p->description = $p->title .' description';
        // $p->summary = $p->title .' summary';
        // $topic = $this->container()->get('lychee.module.topic');
        // $p->coverImageUrl = $p->indexImageUrl = 'http://images.sports.cn/Image/2014/07/09/0757188041.jpg';
        // $topic->create($p);

        // exit();
        // $nextCursor = 0;
        // $a = $this->getContainer()->get('lychee.module.recommendation.group_posts');
        // $r = $a->randomListPostIdsInGroup(4, 20, $nextCursor);
        // print_r($r);exit();


        // 发贴

        $postModule = $this->getContainer()->get('lychee.module.post');

        $parameter = new \Lychee\Module\Post\PostParameter();

        $topicId = 2;
        $content = date('Y-m-d H:i:s').' 发。';

        $parameter->setTopicId($topicId);
        $parameter->setContent($content);

        $videoUrl = $audioUrl = $siteUrl = '';
        $imageUrl = 'http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/e85209d07447398155567673211/7447398155567673213.jpg';
        $videoUrl = 'http://1251120002.vod2.myqcloud.com/8cebefadvodgzp1251120002/e85209d07447398155567673211/n0wAX6iwoZ0A.mp4';

        $annotation = array();
        $annotation['video_cover_width'] = 636;
        $annotation['video_cover'] = $imageUrl;
        $annotation = json_encode($annotation);

        $parameter->setAuthorId(1);
        $parameter->setAuthorLevel(1);
        $parameter->setResource($imageUrl, $videoUrl, $audioUrl, $siteUrl);
        $parameter->setAnnotation($annotation);
        $parameter->setSvId('aaabbaas');
        $parameter->setBgmId(1);

        $type = 7;
        $parameter->setType($type);
        $post = $postModule->create($parameter);
        var_dump($post);

        // curl -X "POST" -d "_format=json&access_token=7aee103f98d974d0be1d062efd83ffe32110c6a0&topic_id=1&content=hahhahahaha&nonce=a&sig=b&bgm_id=0" -H "Content-type:\ application/x-www-form-urlencoded" http://127.0.0.1:80/post/create

        exit();

        // 7aee103f98d974d0be1d062efd83ffe32110c6a0

        // 登陆token
        $userId = 1;
        $token = $this->authentication()->createTokenForUser($userId, \Lychee\Module\Authentication\AuthenticationService::GRANT_TYPE_EMAIL, false);

        var_dump($token);

        exit();

        // 用户登陆

        $user = $this->account()->createWithPhone(15812431454, 86);
        var_dump($user);
        exit();

        // 渲染帖子列表
        $postIds = [1,2];
        $synthesizer = $this->getSynthesizerBuilder()->buildListPostSynthesizer($postIds, 0);
        var_dump($synthesizer);
        $result = $synthesizer->synthesizeAll();
        var_dump($result);

        exit();

        // 帖子与分组关联
        $settleProcessor = $this->container()->get('lychee.module.recommendation.group_posts_settle_processor');
        $postIds = [1, 2, 3];
        $result = $settleProcessor->process($postIds);
        var_dump($result);


        exit();

        // 上传到七牛云
        $storage = $this->container()->get('lychee.component.storage');
        $this->tmpFile = '/data1/tmp/444.mp3';
        $key = 'dev/'.md5($this->tmpFile);
        $url = $storage->put($this->tmpFile, $key);
        echo $url;
        exit();
    }
}
