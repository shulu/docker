<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 8/2/16
 * Time: 8:28 PM
 */

namespace Lychee\Module\Recommendation\Task;


use Lychee\Bundle\CoreBundle\Entity\Post;

class BilibiliSite implements Observer {

    public function getName()
    {
        return 'bilibili';
    }

    public function doActor() {
        $topicId = 50325;
        $categories = [
            1 => ['动画', 1145439],
            3 => ['音乐', 1145456],
            129 => ['舞蹈', 1145470],
            160 => ['生活', 1145479],
            119 => ['鬼畜', 1145489],
            4 => ['游戏', 1145503],
        ];
        $urlScheme = 'http://www.bilibili.com/index/rank/all-1-%d.json';
        $result = [];
        foreach ($categories as $cid => $nav) {
            $name = $nav[0];
            $authorId = $nav[1];
            $url = sprintf($urlScheme, $cid);
            $data = file_get_contents($url);
            if ($data) {
                $data = gzdecode($data);
                if ($data = json_decode($data)) {
                    $list = $data->rank->list;
                    foreach ($list as $row) {
                        // 只发五分钟内的视频
                        if ($this->getDurationSec($row->duration) <= 300) {
                            $result[$row->aid] = [
                                'authorId' => $authorId,
                                'topicId' => $topicId,
                                'thumbnail' => $row->pic,
                                'content' => $row->title,
                                'videoUrl' => sprintf('http://www.bilibili.com/video/av%s/', $row->aid),
                                'type' => Post::TYPE_VIDEO,
                            ];
                        }
                    }
                }
            }
        }
        return $result;
    }

    private function getDurationSec($duration) {
    	var_dump($duration);
	    $time = explode(':', $duration);
	    if (is_array($time)) {
	    	if (isset($time[1])) {
	    		list($min, $sec) = $time;
			    return $min * 60 + $sec;
		    } else {
		    	return $time[0];
		    }
	    } else {
	    	return 0;
	    }
    }

}