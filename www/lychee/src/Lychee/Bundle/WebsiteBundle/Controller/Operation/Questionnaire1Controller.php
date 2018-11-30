<?php
namespace Lychee\Bundle\WebsiteBundle\Controller\Operation;

use Doctrine\DBAL\Connection;
use Lychee\Bundle\CoreBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Questionnaire1Controller extends Controller {

    private function getQuestions() {
        return [
            ['title' => '您的基本属性是？', 'options' =>
                ['♂', '♀']],
            ['title' => '你的年龄是？', 'options' =>
                ['10岁及以下', '11-13岁', '14-16岁', '17-19岁', '20-22岁', '23-25岁', '26岁及以上的魔法师']],
            ['title' => '您的江湖称号为？', 'options' =>
                ['带着红领巾的小学生', '每天嘿嘿嘿的初中生', '沉浸学业中的高中生或中专生', '死宅在寝室的本科生或大专生',
                    '？？？黑人脸的硕士', '要被论文逼疯的博士']],
            ['title' => '您的角色职业为？', 'options' =>
                ['学生狗', '公务员或事业单位员工', '企业搬砖员工', '自由职业者', '待业青年']],
            ['title' => '您现在所处的地图为？', 'options' =>
                ['直辖市（北京、上海、天津、重庆）', '省会主城区（省会城市）', '一般城市（其他地级市）', '十八线县城',
                    '县城外等未知地图（其他集镇）', '新手村（农村地区）']],
            ['title' => '您每月可支配的金钱为？', 'options' =>
                ['500及以下', '501-1500', '1501-2500', '2501-3500', '3501-4500', '4501以上']],
            ['title' => '您平时玩手机游戏吗？', 'options' =>
                ['我才不玩', '当然要玩']],


            ['title' => '您玩手机游戏的频率为？', 'options' =>
                ['每天登录多次，完全停不下来', '每天登录一次，频率规律', '一周登录4次以上', '一周登陆1-3次', '禁欲党，从来不玩']],
            ['title' => '您平均每次的登录持♂续时长为？', 'options' =>
                ['30分钟以内', '0.5-1.5小时', '1.5-2.5小时', '2.5-3.5小时', '3.5小时以上']],
            ['title' => '您通常选择在哪个时间段玩手机游戏？（多选，最多三个）', 'atMost' => 3, 'options' =>
                ['摸鱼党：早上', '日常党：下午', '休闲党：晚上', '夜猫党：深夜']],
            ['title' => '您通常在什么地点玩手机游戏？（多选，最多三个）', 'atMost' => 3, 'options' =>
                ['自己家中', '学校里或工作场所', '上下班、上学放学路途中', '休闲娱乐场所', '其他场所__']],
            ['title' => '您会因为什么样的原因开始玩一款手机游戏？', 'options' =>
                ['截图和介绍很吸引人', '被基友安利入坑', '看到广告觉得好玩', '应用商店推荐', '同人作品入坑', '其他原因__']],
            ['title' => '您在挑选游戏时最看重游戏的什么性质？', 'options' =>
                ['游戏类型', '背景题材', '美术风格', '是否付费', '游戏难度', '玩家数量', '游戏评价', '其他__']],
            ['title' => '您在游戏中的好友主要是？', 'options' =>
                ['三次元的小伙伴', '社交网络上的好基友', '纯游戏好友', '孤独终老']],
            ['title' => '和游戏好友互动（包括赠送体力、聊天、邀请战斗、助战等）的频率？', 'options' =>
                ['总是互动，无基友不游戏', '经常互动，I need AV(安慰)', '偶尔互动，寂寞如影随行', '从不互动，并没有人来爱我', '我13题选了D']],
            ['title' => '您会参加手机游戏中的活动吗？', 'options' =>
                ['总是参加', '经常参加', '有时参加', '偶尔参加', '从不参加']],
            ['title' => '您在手机游戏上一共充过多少钱？', 'options' =>
                ['没钱！从来没充过钱 ', '氪金穷人：1元-200元', '氪金达人：201元-1000元', '氪金富人：1001元-5000元',
                    '氪金土壕：5000元以上']],
            ['title' => '您一共花费过多少钱购买付费手游？', 'options' =>
                ['从没买过付费手游，穷逼狗', '1元-50元，也就一顿饭钱', '51元-200元，也就一顿饭钱', '200元以上，也就一顿饭钱']],


            ['title' => '您的手机里一共有几款手游？', 'options' =>
                ['1-3款', '3-6款', '7-9款', '10款以上']],
            ['title' => '您喜欢哪些手机游戏类型？（多选，最多选三项）', 'atMost' => 3, 'options' =>
                ['角色扮演类（如梦幻西游）', '卡牌类（如我叫MT）', '动作类（如功夫熊猫）', '射击类（如雷霆战机）', '模拟类（如暖暖环游世界）',
                    '休闲类（如开心消消乐）', '音乐类（如LoveLive！）', '经营类（如牛郎店的危险世界）', '其他类型__']],
            ['title' => '您喜欢哪些题材的手机游戏？（多选，最多选三项）', 'atMost' => 3, 'options' =>
                ['冒险', '战争', '少女', '鬼畜', '恐怖', '日常', '美食', '科幻', '历史', '音乐', '影视', '其他__']],
            ['title' => '您对手机游戏难度的期望是？', 'options' =>
                ['难一点更好，终生EX模式', '正常难度，别总想搞个大新闻', '简单一些，放过手残党', '无所谓']],
            ['title' => '您手机里的游戏都是哪家公司制作的？（多选，最多三项）', 'atMost' => 3, 'options' =>
                ['腾讯爸爸', '网易土豪', '盛大大大', '掌趣游戏', '莉莉丝游戏', '多益网络', '乐元素', '没注意过']],
        ];
    }

    /**
     * @Route("/operation/questionnaire")
     * @Method("GET")
     */
    public function getQuestionnaire1Action(Request $request) {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }

        if ($request->query->has('filled_by')) {
            $phone = $request->query->getInt('filled_by');
            if ($this->phoneIsValid($phone) && $this->hasFilled($phone)) {
                return new Response('', 200);
            } else {
                return new Response('', 404);
            }
        } else {
            $questions = $this->getQuestions();
            return $this->render('LycheeWebsiteBundle:Operation:questionnaire1.html.twig', array('questions' => $questions));
        }
    }

    /**
     * @param int $phone
     * @return bool
     */
    private function phoneIsValid($phone) {
        return 10000000000 <= $phone && $phone <= 99999999999;
    }

    /**
     * @Route("/operation/questionnaire")
     * @Method("POST")
     */
    public function postQuestionnaire1Action(Request $request) {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }

        $phone = $request->request->getInt('phone');
        if ($this->phoneIsValid($phone) == false) {
            return $this->render('LycheeWebsiteBundle:Operation:questionnaire1.html.twig', array('questions' => $this->getQuestions()));
        }
        if ($this->hasFilled($phone)) {
            return $this->render('LycheeWebsiteBundle:Operation:conclusion1.html.twig', array('conclusion' => rand(1, 5)));
        }

        $r = $request->request->all();
        $questions = $this->getQuestions();
        $result = array();
        try {
            for ($i = 1; $i <= count($questions); ++$i) {
                $question = $questions[$i - 1];
                $atMost = isset($question['atMost']) ? $question['atMost'] : 1;
                $options = $question['options'];
                $optValueParam = isset($r['q' . $i]) ? $r['q' . $i] : null;
                if ($optValueParam) {
                    $optValues = explode(',', $optValueParam);
                    if (count($optValues) > $atMost || empty($optValues)) {
                        throw new \Exception('invalid option count');
                    }
                    $optValues = array_unique(array_map('intval', $optValues));
                    foreach ($optValues as $v) {
                        if ($v <= 0 || $v > count($options)) {
                            throw new \Exception('invalid option value');
                        }

                        $o = $options[$v - 1];
                        $ei = 'q' . $i . '_e' . $v;
                        if (strrpos($o, '__') == strlen($o) - 2 && isset($r[$ei])) {
                            $result[$ei] = mb_substr($r[$ei], 0, 100);
                        }
                    }

                    $optValue = array_reduce($optValues, function($s, $i){ return $s | (1 << ($i - 1));}, 0);
                    $result['q'.$i] = $optValue;
                }
            }
        } catch (\Exception $e) {
            return $this->render('LycheeWebsiteBundle:Operation:questionnaire1.html.twig', array('questions' => $questions));
        }
        $this->saveResult($phone, $result);

        if ($result['q7'] == 1) {
            return $this->render('LycheeWebsiteBundle:Operation:conclusion1.html.twig', array('conclusion' => 0));
        } else {
            $fillSeq = $this->getFillSeq($phone);
            if ($fillSeq !== null && $fillSeq <= 500) {
                $phoneType = $this->getPhoneType($phone);
                if ($phoneType == 0) {
                    $alert = null;
                } else if ($phoneType == 1) {
                    $alert = '答题完毕！掉落手机流量×70M，奖励将在8月4~5日发放。';
                } else {
                    $alert = '答题完毕！掉落手机流量×100M，奖励将在8月4~5日发放。';
                }
            } else {
                $alert = null;
            }

            return $this->render('LycheeWebsiteBundle:Operation:conclusion1.html.twig', array('conclusion' => rand(1, 5), 'alert' => $alert));
        }
    }

    /**
     * @param int $phone
     * @return int 0-其他, 1-移动, 2-联通, 3-电信
     */
    public function getPhoneType($phone) {
        if ($phone < 13000000000) { return 0; } else if ($phone < 13300000000) { return 2; } else if ($phone < 13400000000) { return 3; }
        else if ($phone < 13490000000) { return 1; } else if ($phone < 13500000000) { return 3; } else if ($phone < 14000000000) { return 1; }
        else if ($phone < 14500000000) { return 0; } else if ($phone < 14600000000) { return 2; } else if ($phone < 14700000000) { return 0; }
        else if ($phone < 14800000000) { return 1; } else if ($phone < 15000000000) { return 0; } else if ($phone < 15300000000) { return 1; }
        else if ($phone < 15400000000) { return 3; } else if ($phone < 15500000000) { return 0; } else if ($phone < 15700000000) { return 2; }
        else if ($phone < 16000000000) { return 1; } else if ($phone < 17000000000) { return 0; } else if ($phone < 17200000000) { return 0; }
        else if ($phone < 17600000000) { return 0; } else if ($phone < 17700000000) { return 2; } else if ($phone < 17800000000) { return 3; }
        else if ($phone < 17900000000) { return 1; } else if ($phone < 18000000000) { return 0; } else if ($phone < 18200000000) { return 3; }
        else if ($phone < 18500000000) { return 1; } else if ($phone < 18700000000) { return 2; } else if ($phone < 18900000000) { return 1; }
        else if ($phone < 19000000000) { return 3; } else { return 0; }
    }

    /**
     * @Route("/operation/questionnaire/_ddl")
     * @Method("GET")
     */
    public function getSqlAction() {
        $ddl = $this->buildCreateDbSql($this->getQuestions());
        return new Response($ddl);
    }

    /**
     * @return Connection
     */
    private function mysql() {
        return $this->get('doctrine')->getConnection();
    }

    private function buildCreateDbSql($questions) {
        $fields = array(
            'id BIGINT NOT NULL AUTO_INCREMENT',
            '`phone` BIGINT NOT NULL');
        for ($i = 0; $i < count($questions); ++$i) {
            $q = $questions[$i];
            $qi = $i + 1;
            $fields[] = "`q$qi` SMALLINT DEFAULT NULL";
            $opts = $q['options'];
            for ($ii = 0; $ii < count($opts); ++$ii) {
                $opt = $opts[$ii];
                $oi = $ii + 1;
                if (strrpos($opt, '__') == strlen($opt) - 2) {
                    $fields[] = "`q{$qi}_e{$oi}` VARCHAR(100) DEFAULT NULL";
                }
            }
        }
        $fields[] = 'PRIMARY KEY (id)';
        $fields[] = 'INDEX (`phone`)';
        return "DROP TABLE IF EXISTS ciyocon_oss.`questionnaire1`;\n"
            .'CREATE TABLE ciyocon_oss.`questionnaire1` ('.implode(',', $fields).') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
    }

    private function hasFilled($phone) {
        $sql = 'SELECT 1 FROM ciyocon_oss.questionnaire1 WHERE phone = ?';
        $stat = $this->mysql()->executeQuery($sql, array($phone), array(\PDO::PARAM_INT));
        $row = $stat->fetch(\PDO::FETCH_NUM);
        if ($row) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int $phone
     * @return null|int
     */
    private function getFillSeq($phone) {
        $sql = 'SELECT count(*) FROM ciyocon_oss.questionnaire1 WHERE id <= '
            .'(SELECT id FROM ciyocon_oss.questionnaire1 WHERE phone = ?)';
        $stat = $this->mysql()->executeQuery($sql, array($phone), array(\PDO::PARAM_INT));
        $row = $stat->fetch(\PDO::FETCH_NUM);
        if ($row) {
            return intval($row[0]);
        } else {
            return null;
        }
    }

    /**
     * @param int $phone
     * @param array $result
     * @return bool
     */
    private function saveResult($phone, $result) {
        $result['phone'] = $phone;

        $fields = array();
        $values = array();
        $params = array();
        foreach ($result as $k => $v) {
            $fields[] = $k;
            $values[] = $v;
            $params[] = is_int($v) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
        }

        $sql = 'INSERT INTO ciyocon_oss.questionnaire1('.implode(',', $fields).') VALUES('
            .implode(',', array_pad(array(), count($values), '?')).') ON DUPLICATE KEY UPDATE phone = phone';
        $affected = $this->mysql()->executeUpdate($sql, $values, $params);
        return $affected > 0;
    }

}