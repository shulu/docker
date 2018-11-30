<?php
namespace Lychee\Module\ContentAudit\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="image_review_audit_config", indexes={
 * })
 */
class ImageReviewAuditConfig {

    /*
     * 内容审核策略配置
     */
    //    色情确定评分配置项id
    const TRASH_PORN_SURE_MIN_RATE_ID =1;

    //    色情不确定评分配置项id
    const TRASH_PORN_UNSURE_MIN_RATE_ID =2;

    //    性感确定评分配置项id
    const TRASH_SEXY_SURE_MIN_RATE_ID =3;

    //    性感确定评分配置项id
    const TRASH_SEXY_UNSURE_MIN_RATE_ID =4;


    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint", options={"unsigned":true})
     * @ORM\Id
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=60)
     */
    public $title;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=255)
     */
    public $value;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=1000, nullable=true)
     */
    public $description;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_time", type="datetime", options={"comment":"更新时间"})
     */
    public $updateTime;


    public static function getDefaultConfigs() {
        $configs = [];
        $configs[self::TRASH_PORN_SURE_MIN_RATE_ID] = ['确定色情阈值', 0, '范围0~101，鉴黄结果为确定、色情并且概率>=该值，即自动删除图片'];
        $configs[self::TRASH_PORN_UNSURE_MIN_RATE_ID] = ['疑似色情阈值', 101, '范围0~101，鉴黄结果为疑似、色情并且概率>=该值，即自动删除图片'];
        $configs[self::TRASH_SEXY_SURE_MIN_RATE_ID] = ['确定性感阈值', 101, '范围0~101，鉴黄结果为确定、性感并且概率>=该值，即自动删除图片'];
        $configs[self::TRASH_SEXY_UNSURE_MIN_RATE_ID] = ['疑似性感阈值', 101, '范围0~101，鉴黄结果为疑似、性感并且概率>=该值，即自动删除图片'];
        return $configs;
    }
}