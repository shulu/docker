<?php
namespace app\module\account;

class NicknameGenerator {

    static $modifiers = array('忧郁', '灵活', '傲娇', '腹黑', '毒舌', '萌萌哒', '高颜值', '哦摩西裸衣',
        '恶趣味', '怨念', '口胡', '逆天', '爆炸', '进击', '热血', '暴走', '销魂', '中二', '没救', '愚蠢',
        '旋转', '欢脱', '贱萌', '呆萌', '贫乳', '变弯', '潜水', '强大', '弱鸡', '词穷', '口吃', '天然呆',
        '冒失', '吐槽', '弱气', '不科学', '脸盲', '崩坏', '悲伤', '迟钝', '绝望', '格式化', '目害', '炸裂',
        '上天', '折翼', '冒牌', '伪造', '撒鼻息', '鸡冻', '寂寞', '墨迹', '拖延', '氪金', '肝图', '怕生',
        '一米六', '抖腿', '穿越', '开挂', '痛苦', '嚣张', '发光', '闪烁', '正直', '文艺', '愤怒', '颜艺',
        '严肃', '搞基', '卖萌', '机智', '狂气', '欠揍', '过气', '高贵', '高冷', '面瘫', '最高校级', '温油',
        '粗壮', '路过', '围观', '闹事', '黑化', '觉醒', '流弊', '魔性', '跑圈', '透明', '领便当', '加鸡腿',
        '喧嚣', '平静', '抠脚', '坏笑', '手残', '脑残', '及格', '翻滚', '飞翔');

    static $nounals = array('2b', '逗比', '凹凸曼', '美少女', '胖子', '萌二', '死宅', '正太', '御姐',
        '大叔', '弱受', '强攻', '总攻', '总犬攻', '伪娘', '软妹子', 'loli', '幼齿', '中二病', '深井冰',
        '单身狗', '脱团狗', '废柴', '兄贵', '大姐姐', '黑猫', '芬达', '妹抖', 'JK', '发际线', 'hentai',
        '绅士', '爆炸头', '双马尾', '秃头', '死', '三无少女', '干物女', '抖S', '抖M', '呆毛', '黑长直',
        '乙女', '执事', '胖次', '打底裤', '反射弧', '平面', '洗衣板', '腐女', '便当', '邪王真眼', '女神',
        '脱团狗', '兽耳控', '绝对领域', '女王', '前辈', '老司机', '义妹', '病娇', '小眼镜', '电磁炮', '秀吉',
        'doge', '打工战士', '吐槽狂魔', '鲁鲁修', '新八鸡', '怪蜀黍', 'VIP', '吃货', '二货', '神兽', '魔法师',
        '辅助', '萌神', '小透明', '右手', '聚聚', '拖延症', 'ller', '巨人', '八嘎', 'AHO', '非主流', '杀马特',
        '海贼王', '爱豆', '小圆脸', '表情包', '欧洲人', '非洲人', '提督', '迷妹', '熊孩子', '动作废', '比利',
        '路人', '死鱼眼', '马猴烧酒');

    static $auxiliaries = array('的', 'の', '滴');

    /**
     * @return string
     * @throws \LogicException
     */
    public function generate() {
        $presetName = $this->generatePresetName();
        $maxNumberLength = min((36 - strlen($presetName)), 10);
        $minNumberLength = min($maxNumberLength - 1, 4);
        if ($maxNumberLength <= 0 || $minNumberLength <= 0) {
            throw new \LogicException('error preset name string length.');
        }
        $rnd = $this->generateRandomNumber(pow(10, $minNumberLength), pow(10, $maxNumberLength));
        return $presetName.$rnd;
    }

    private function generatePresetName() {
        $modifier = self::$modifiers[$this->generateRandomNumber(0, count(self::$modifiers))];
        $auxiliary = self::$auxiliaries[$this->generateRandomNumber(0, count(self::$auxiliaries))];
        $nounal = self::$nounals[$this->generateRandomNumber(0, count(self::$nounals))];
        return $modifier.$auxiliary.$nounal;
    }

    private function generateRandomNumber($min, $max) {
        return (int)floor((hexdec(bin2hex(openssl_random_pseudo_bytes(4))) / 0xffffffff) * ($max - $min)) + $min;
    }
}