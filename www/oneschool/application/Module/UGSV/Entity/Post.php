<?php
namespace Lychee\Module\UGSV\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="ugsv_post", indexes={
 *    @ORM\Index(name="sv_id", columns={"sv_id"}),
 *    @ORM\Index(name="bgm_id", columns={"bgm_id"}),
 *    @ORM\Index(name="author_id", columns={"author_id"}),
 *    @ORM\Index(name="playcount", columns={"playcount"})
 * })
 */
class Post {

    /**
     * @var int
     *
     * @ORM\Column(name="post_id", type="bigint", options={"unsigned":true})
     * @ORM\Id
     */
    public $postId;

    /**
     * @var string
     *
     * @ORM\Column(name="sv_id", type="string", length=255, options={"comment":"视频文件id"})
     */
    public $svId;

    /**
     * @var int
     *
     * @ORM\Column(name="bgm_id", type="bigint", options={"unsigned":true, "comment":"背景音乐id"})
     */
    public $bgmId;

    /**
     * @var int
     *
     * @ORM\Column(name="author_id", type="integer", options={"unsigned":true, "comment":"作者用户id"})
     */
    public $authorId;

    /**
     * @var int
     *
     * @ORM\Column(name="playcount", type="bigint", options={"unsigned":true, "comment":"播放次数"})
     */
    public $playCount;


}