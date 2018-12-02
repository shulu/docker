<?php
namespace Lychee\Module\Post\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="post_audit", indexes={
 *    @ORM\Index(name="status_update_time", columns={"status", "update_time"}),
 *    @ORM\Index(name="status_source_update_time", columns={"status", "source", "update_time"})
 * })
 */
class PostAudit {

    // 通过
    const PASS_STATUS =1;
    // 不通过
    const NOPASS_STATUS =2;
    // 未审核
    const UNTREATED_STATUS =3;

//    后台审核
    const ADMIN_SOURCE = 1;
//    腾讯云点播智能鉴黄
    const TXVOD_AI_REVIEW_PORN_SOURCE = 2;

    /**
     * @var int
     *
     * @ORM\Column(name="post_id", type="bigint", options={"unsigned":true})
     * @ORM\Id
     */
    public $postId;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", options={"unsigned":true, "default":"3", "comment":"1：审核通过，2：审核不通过，3：未审核"})
     */
    public $status = 3;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_time", type="datetime", options={"comment":"更新时间"})
     */
    public $updateTime;

    /**
     * @var int
     * 
     *
     * @ORM\Column(name="source", type="smallint", options={"unsigned":true, "default":"1", "comment":"审核来源"})
     */
    public $source = 1;

}