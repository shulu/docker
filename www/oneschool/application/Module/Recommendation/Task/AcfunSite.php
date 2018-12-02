<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 8/2/16
 * Time: 8:28 PM
 */

namespace Lychee\Module\Recommendation\Task;


use Lychee\Bundle\CoreBundle\Entity\Post;

class AcfunSite implements Observer {

    public function getName()
    {
        return 'acfun';
    }

    public function doActor() {
        $topicId = 50431;
        $categories = [
            60 => ['娱乐', 1210689],
            59 => ['游戏', 1210695],
            58 => ['音乐', 1210698],
            123 => ['舞蹈', 1210702],
            124 => ['彼女', 1210707],
        ];
        $urlScheme = 'http://www.acfun.tv/rank.aspx?channelId=%s&range=1&count=30&ext=1&date=';
        $result = [];
        foreach ($categories as $cid => $nav) {
            $name = $nav[0];
            $authorId = $nav[1];
            $url = sprintf($urlScheme, $cid);
            $data = file_get_contents($url);
            if ($data) {
                if ($data = json_decode($data)) {
                    foreach ($data as $row) {
                        $url = $row->url;
                        if (preg_match('/ac(\d+)/i', $url, $matches)) {
                            $id = $matches[1];
                            $result[$id] = [
                                'authorId' => $authorId,
                                'topicId' => $topicId,
                                'thumbnail' => $row->titleImg,
                                'content' => $row->title,
                                'videoUrl' => sprintf('http://www.acfun.tv%s', $row->url),
                                'type' => Post::TYPE_VIDEO,
                            ];
                        }
                    }
                }
            }
        }
        return $result;
    }

}