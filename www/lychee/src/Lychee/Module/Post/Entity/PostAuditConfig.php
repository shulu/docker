<?php
namespace Lychee\Module\Post\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="post_audit_config", indexes={
 * })
 */
class PostAuditConfig {

    /*
     * 内容审核策略配置
     */
    //    配置项id
    const STRATEGY_ID =1;
    //    发帖后需要审核
    const OPEN_STRATEGY_VALUE =1;
    //    发帖后不需要审核
    const CLOSE_STRATEGY_VALUE =2;

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
}