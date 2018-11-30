<?php
namespace Lychee\Bundle\WebsiteBundle\Controller\Operation;

use Lychee\Bundle\CoreBundle\Controller\Controller;
use Lychee\Component\Foundation\ArrayUtility;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

class TwoYearAnniversaryQuestionnaireConstroller extends Controller {

    /**
     * @Route("/operation/tow_year_anniversary_questionnaire")
     * @Method("GET")
     */
    public function getQuestionnaireAction() {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }


        $questions = $this->getQuestions();

        list($judge, $single) = ArrayUtility::separate($questions, function($q){return isset($q['questionType']) && $q['questionType'] == 'judge';});

        return $this->render('LycheeWebsiteBundle:Operation:2-year-anniversary-questionnaire.html.twig', array('singleQuestions' => $single, 'judgeQuestions' => $judge));
    }

    /**
     * @Route("/operation/tow_year_anniversary_questionnaire")
     * @Method("POST")
     */
    public function getResultAction(Request $request) {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }

        $r = $request->request->all();
        $questions = $this->getQuestions();

        $score = 0;
        for ($i = 1; $i <= count($questions); ++$i) {
            $question = $questions[$i - 1];
            $optValueParam = isset($r['q' . $i]) ? $r['q' . $i] : null;
            if ($optValueParam == null || (isset($question['answer']) && intval($optValueParam) != $question['answer'])) {
                $score -= isset($question['questionType']) && $question['questionType'] == 'judge' ? 50 : 75;
            }
        }

        if ($score >= -200) {
            $result = 1;
        } else if ($score >= -400) {
            $result = 2;
        } else if ($score >= -600) {
            $result = 3;
        } else if ($score >= -800) {
            $result = 4;
        } else {
            $result = 5;
        }

        return $this->render('LycheeWebsiteBundle:Operation:2-year-anniversary-questionnaire-conclusion.html.twig', array('result' => $result, 'score' => $score / 10));
    }

    private function getQuestions() {
        return [[
            'title'=> '下列对次元社的描述正确的是：',
            'options'=> ['网红次元酱的专属后宫 ', '老司机集聚的飙车圣地', '健康积极的二次元社区', '死宅的干净PY交易场所'],
            'answer' => 3,
            'optionType'=> 'text'
        ], [
            'title'=> '以下哪个是次元酱？',
            'options'=> ['http://qn.ciyocon.com/web/operation/2-year-anniversary/a.png', 'http://qn.ciyocon.com/web/operation/2-year-anniversary/b.png', 'http://qn.ciyocon.com/web/operation/2-year-anniversary/c.png', 'http://qn.ciyocon.com/web/operation/2-year-anniversary/d.png'],
            'answer' => 3,
            'optionType'=> 'image'
        ], [
            'title'=> '下面哪种帖子会被次元酱无情的吞噬？',
            'options'=> ['深夜福利 出售百度云资源2000g只要两元！仅此今晚！加我QQxxxxx', '招聘文字录入员： 1000字35￥工资日结', '老司机开车啦！抓紧时间上车！你懂得！', '以上都会'],
            'answer' => 4,
            'optionType'=> 'text'
        ], [
            'title'=> '下面哪一个是次元社1.5的版本界面？(点击图片查看大图）',
            'options'=> ['http://qn.ciyocon.com/web/operation/2-year-anniversary/a1.png', 'http://qn.ciyocon.com/web/operation/2-year-anniversary/b1.png', 'http://qn.ciyocon.com/web/operation/2-year-anniversary/c1.png', 'http://qn.ciyocon.com/web/operation/2-year-anniversary/d1.png'],
            'answer' => 2,
            'optionType'=> 'image'
        ], [
            'title'=> '次元酱的属性是？',
            'options'=> ['天真纯良圣母 ', '软萌可爱萝莉', '性感长腿御姐', '去他的属性'],
            'answer' => 4,
            'optionType'=> 'text'
        ], [
            'title'=> '次元酱和次元娘的关系是？',
            'options'=> ['情侣 ', '父子', '隔壁老邻居', '同一个人'],
            'answer' => 4,
            'optionType'=> 'text'
        ], [
            'title'=> '下图中一共有几个次元娘？',
            'options'=> ['13个 ', '15个', '10个', '12个'],
            'answer' => 1,
            'optionType'=> 'text',
            'attachment'=> 'http://qn.ciyocon.com/web/operation/2-year-anniversary/screenshot.png'
        ], [
            'title'=> '你可以在次元社上可以做的事情有：',
            'options'=> ['听音乐 ', '找基友', '存壁纸', '捞表情', '找资源 ', '唠嗑', '晒自拍', '做我想做♂的'],
            'answer' => 8,
            'optionType'=> 'text'
        ], [
            'title'=> '在创建新次元时，颜色选项中的第六种颜色是：',
            'options'=> ['基佬紫 ', '司机黄', '老王绿', '夕阳红'],
            'answer' => 1,
            'optionType'=> 'text'
        ], [
            'title'=> '新手小红点经过不断努力，终于在次元社里脱了团，那么我们应该要做的事情是？',
            'options'=> ['我也要去次元社里脱团    ', '抱紧自己在墙角瑟瑟发抖', '对不起，我早就脱团了', 'D.FFFFFFFFFFFFFFF'],
            'answer' => 4,
            'optionType'=> 'text'
        ], [
            'title'=> '次元酱的形象设计来源于冷静的包子。',
            'options'=> ['√', '×'],
            'answer' => 2,
            'optionType'=> 'text',
            'questionType'=> 'judge'
        ], [
            'title'=> '司机新手小红点将昵称更改为“小红点的名字很长很长很长”，符合ID昵称字符长度。',
            'options'=> ['√', '×'],
            'answer' => 1,
            'optionType'=> 'text',
            'questionType'=> 'judge'
        ], [
            'title'=> '【次元娘的生活日记】条漫，最早出现于公元2016年6月18日18点26分。',
            'options'=> ['√', '×'],
            'answer' => 1,
            'optionType'=> 'text',
            'questionType'=> 'judge'
        ], [
            'title'=> '已知老司机次元酱233级，每10级有2次创建新次元的特权，那么次元酱一共有46次创建新次元的特权。',
            'options'=> ['√', '×'],
            'answer' => 2,
            'optionType'=> 'text',
            'questionType'=> 'judge'
        ], [
            'title'=> '次元娘是次元社第一美少女。',
            'options'=> ['√', '√'],
            'optionType'=> 'text',
            'questionType'=> 'judge'
        ]];
    }

}
