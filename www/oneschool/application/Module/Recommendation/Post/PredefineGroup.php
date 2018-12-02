<?php
namespace Lychee\Module\Recommendation\Post;

class PredefineGroup extends AbstractGroup {

    const ID_INDEX = 1;
    const ID_VIDEO = 2;
    const ID_GIF = 3;
    const ID_JINGXUAN = 4;
	const ID_LIVE = 5;

    const ID_BIAOQINGBAO = 101;
    const ID_ZIPAI = 102;
    const ID_COSPLAY = 103;
    const ID_BIZHITOUXIANG = 104;
    const ID_JIAOYOU = 105;
    const ID_FU = 106;

    const ID_XINGQU = 301;
    const ID_HUODONG = 302;
    const ID_DONGMAN = 303;
    const ID_YOUXI = 304;
    const ID_SHENGHUO = 305;
    const ID_DOUBI = 306;
    const ID_SHETUAN = 307;
    const ID_OUXIANG = 308;
    const ID_YINGSHI = 309;
    const ID_RICHANG = 310;
    const ID_HUIHUA = 311;
    const ID_YOUXI2 = 312;
    const ID_DONGMAN2 = 313;
    const ID_XINGQU2 = 314;
    const ID_COSPLAY_TOPIC = 315;
    const ID_ZHAIWU = 316;
    const ID_COSPLAY_TAB = 317;
    const ID_ZHAI_WU_TAB = 318;
    const ID_ZHAI_XIANG_TAB = 319;
    const ID_ZIPAI_TAB = 320;
    const ID_DACHU_TAB = 321;
    const ID_BEAUTIFUL_PIC_TAB = 322;
    const ID_3CY_TAB = 323;

    static private $predefineGroups = null;

    /**
     * @return Group[]
     */
    static public function groups() {
        if (self::$predefineGroups === null) {

            self::$predefineGroups = [];
            self::$predefineGroups[self::ID_INDEX] = new PredefineGroup(self::ID_INDEX, '推荐', new AllGroupResolver(self::ID_INDEX));
            self::$predefineGroups[self::ID_JINGXUAN] = new PredefineGroup(self::ID_JINGXUAN, '精选', new AllGroupResolver(self::ID_JINGXUAN));
            self::$predefineGroups[self::ID_VIDEO] = new PredefineGroup(self::ID_VIDEO, '视频', new VideoGroupResolver(self::ID_VIDEO));
            self::$predefineGroups[self::ID_GIF] = new PredefineGroup(self::ID_GIF, '动图', new GifGroupResolver(self::ID_GIF));
            self::$predefineGroups[self::ID_LIVE] = new PredefineGroup(self::ID_LIVE, '皮哲直播', new AllGroupResolver(self::ID_LIVE));
            self::$predefineGroups[self::ID_HUODONG] = new TopicCategoryGroup(self::ID_HUODONG, '活动', 302);
            self::$predefineGroups[self::ID_DONGMAN] = new TopicCategoryGroup(self::ID_DONGMAN, '动漫', 303);
            self::$predefineGroups[self::ID_YOUXI] = new TopicCategoryGroup(self::ID_YOUXI, '游戏', 304);
            self::$predefineGroups[self::ID_SHENGHUO] = new TopicCategoryGroup(self::ID_SHENGHUO, '生活', 305);
            self::$predefineGroups[self::ID_DOUBI] = new TopicCategoryGroup(self::ID_DOUBI, '逗比', 306);
            self::$predefineGroups[self::ID_SHETUAN] = new TopicCategoryGroup(self::ID_SHETUAN, '社团', 307);
            self::$predefineGroups[self::ID_OUXIANG] = new TopicCategoryGroup(self::ID_OUXIANG, '偶像', 308);
            self::$predefineGroups[self::ID_YINGSHI] = new TopicCategoryGroup(self::ID_YINGSHI, '影视', 309);
            self::$predefineGroups[self::ID_BIAOQINGBAO] = new TopicsGroup(self::ID_BIAOQINGBAO, '表情包', array(29579, 29661, 32129));
            self::$predefineGroups[self::ID_ZIPAI] = new TopicsGroup(self::ID_ZIPAI, '萌妹', array(25150, 25337, 25518, 31252, 35360, 46607, 25078)); // "自拍"改名"萌妹"
            self::$predefineGroups[self::ID_COSPLAY] = new TopicsGroup(self::ID_COSPLAY, 'COS', array(25076, 25354));
            self::$predefineGroups[self::ID_BIZHITOUXIANG] = new TopicsGroup(self::ID_BIZHITOUXIANG, '壁纸头像', array(25497,25511,46853,28711,34753,31167,25473,27925));
            self::$predefineGroups[self::ID_JIAOYOU] = new TopicsGroup(self::ID_JIAOYOU, '交友', array(32872, 31747, 31825, 30727, 35409, 30965, 32352));
            self::$predefineGroups[self::ID_FU] = new TopicsGroup(self::ID_FU, '腐', array(26847, 27557, 25176));
            self::$predefineGroups[self::ID_XINGQU2] = new TopicsGroup(self::ID_XINGQU2, '兴趣', array(27115,25384,33787,26454,25159,25220,34316,28874,25386,35024,41951,27557,25176));
            self::$predefineGroups[self::ID_RICHANG] = new TopicsGroup(self::ID_RICHANG, '日常', array(25109, 29759, 32636, 25617, 25158));
            self::$predefineGroups[self::ID_HUIHUA] = new TopicsGroup(self::ID_HUIHUA, '绘画', array(25362, 25430, 31168, 34016));
            self::$predefineGroups[self::ID_DONGMAN2] = new TopicsGroup(self::ID_DONGMAN2, '动漫', array(25497,25511,46853,28711,34753,31167,25473,27925));
            self::$predefineGroups[self::ID_YOUXI2] = new TopicsGroup(self::ID_YOUXI2, '游戏', array(50194,53634,35601,48064,48019,26082,25183,54639));
            self::$predefineGroups[self::ID_BEAUTIFUL_PIC_TAB] = new TopicsGroup(self::ID_BEAUTIFUL_PIC_TAB, '美图', [27925,29661,25511,25497,28711,25473,46853,32129,34016,29579,34753,31167]);
            self::$predefineGroups[self::ID_DACHU_TAB] = new TopicsGroup(self::ID_DACHU_TAB, '大触', [25362,26454,29759,31168,33787,54237,25386,25430]);
            self::$predefineGroups[self::ID_3CY_TAB] = new TopicsGroup(self::ID_3CY_TAB, '三次元', [32872,35409,25109,25181,25158,31747,31825,32636,30965,32352,30727]);

            $cosPlayTopics = [25076,25354];
            self::$predefineGroups[self::ID_COSPLAY_TOPIC] = new TopicsGroup(self::ID_COSPLAY_TOPIC, 'Cosplay次元', $cosPlayTopics);
            self::$predefineGroups[self::ID_COSPLAY_TAB] = new TopicsGroup(self::ID_COSPLAY_TAB, 'Cosplay', $cosPlayTopics);

            $zaiWuTopics = [54703,54723];
            self::$predefineGroups[self::ID_ZHAIWU] = new TopicsGroup(self::ID_ZHAIWU, '宅舞', $zaiWuTopics);
            self::$predefineGroups[self::ID_ZHAI_WU_TAB] = new TopicsGroup(self::ID_ZHAI_WU_TAB, '宅舞', $zaiWuTopics);

            self::$predefineGroups[self::ID_ZIPAI_TAB] = new TopicsGroup(self::ID_ZIPAI_TAB, '自拍', [35360,25150,25220,35024,41951]);
            self::$predefineGroups[self::ID_ZHAI_XIANG_TAB] = new TopicsGroup(self::ID_ZHAI_XIANG_TAB, '宅向', [36094,27115,25168,25384,25159,25211,28874,25115]);

            /**
             * 精选帖子逻辑调整
             */
            $subGroupIds = GroupManager::getSubGroupIds(self::ID_JINGXUAN);
            $JINGXUANResolver = self::$predefineGroups[self::ID_JINGXUAN]->resolver();
            foreach ($subGroupIds as $subGroupId) {
                if (empty(self::$predefineGroups[$subGroupId])) {
                    continue;
                }
                $subGroupObj = self::$predefineGroups[$subGroupId];
                if (empty($subGroupObj instanceof TopicsGroup)) {
                    continue;
                }
                $JINGXUANResolver->excludeTopics($subGroupObj->getTopicIds());
            }

        }
        return self::$predefineGroups;
    }

    private $_resolver;

    public function __construct($id, $name, $resolver) {
        parent::__construct($id, $name);
        $this->_resolver = $resolver;
    }

    public function resolver() {
        return $this->_resolver;
    }

}