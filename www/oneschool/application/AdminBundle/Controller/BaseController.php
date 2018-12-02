<?php
/**
 * Created by PhpStorm.
 * User: benson
 * Date: 14-10-14
 * Time: 下午4:54
 */

namespace Lychee\Bundle\AdminBundle\Controller;


use Lychee\Bundle\AdminBundle\ServiceAwareTrait;
use Lychee\Bundle\CoreBundle\Controller\Controller;
use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Component\Foundation\ImageUtility;
use Lychee\Module\Post\PostAnnotation;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BaseController
 * @package Lychee\Bundle\AdminBundle\Controller
 */
abstract class BaseController extends Controller {
    use ServiceAwareTrait;

    /**
     * @return mixed
     */
    abstract public function getTitle();

    /**
     * @param null $subTitle
     * @param array $data
     * @return array
     */
    protected function response($subTitle = null, $data = array())
    {
        $return = array(
            'title' => $this->getTitle(),
            'subTitle' => $subTitle,
        );

        return array_merge($return, $data);
    }

    /**
     * 返回原图
     * @param $object
     * @return string
     */
    protected function getOriginalImage( $object)
    {
        $fieldName = 'original_url';
        $annotation = json_decode( $object->annotation);
        if ($annotation && isset($annotation->$fieldName)) {
            return $annotation->$fieldName;
        }

        return $object->imageUrl;
    }

    /**
     * @param Post $post
     * @return array
     */
    protected function getAllImages(Post $post) {
        $images = [];
        $annotation = json_decode($post->annotation);
        if ($annotation) {
            if (isset($annotation->multi_photos)) {
                foreach ($annotation->multi_photos as $image) {
                    $images[] = $image;
                }
            } elseif (isset($annotation->original_url)) {
                $images[] = $annotation->original_url;
            } elseif (isset($post->imageUrl)) {
                $images[] = $post->imageUrl;
            }
        } elseif ($post->imageUrl) {
            $images[] = $post->imageUrl;
        }

        return $images;
    }

    /**
     * @param $content
     * @return mixed
     */
    protected function linkFilter($content)
    {
        return preg_replace('/(http[s]{0,1}:\/\/\S+)/i', '<a href="$1" target="_blank">$1</a>', $content);
    }

    /**
     * @param $postId
     * @return bool
     */
    protected function postInFavor($postId) {
        return $this->adminFavor()->hasPost($postId);
    }

    /**
     * @param $post
     * @return array
     */
    protected function getResource($post) {
        $resource = [];
        $annotation = json_decode($post->annotation);
        if ($annotation) {
            if (isset($annotation->resource_link)) {
                $resource['link'] = $annotation->resource_link;
            } else {
                $resource['link'] = '';
            }
            if (isset($annotation->resource_title)) {
                $resource['title'] = $annotation->resource_title;
            } else {
                $resource['title'] = '';
            }
            if (isset($annotation->resource_thumb)) {
                $resource['thumb'] = $annotation->resource_thumb;
            } else {
                $resource['thumb'] = '';
            }
        }

        return $resource;
    }

    /**
     * @param Post $post
     * @return array
     */
    protected function getImgsInPost(Post $post) {
        $imgs = [];
        if ($post->imageUrl) {
            array_push($imgs, $post->imageUrl);
        }
        $annotation = json_decode($post->annotation);
        if ($annotation) {
            switch($post->type) {
                case Post::TYPE_RESOURCE:
                    $key = PostAnnotation::RESOURCE_THUMB;
                    array_push($imgs, $annotation->$key);
                    break;
                default:
                    $key = PostAnnotation::MULTI_PHOTOS;
                    if (isset($annotation->$key)) {
                        $photos = $annotation->$key;
                        $imgs = array_merge($imgs, $photos);
                    }
                    $key = PostAnnotation::ORIGINAL_URL;
                    if (isset($annotation->$key)) {
                        array_push($imgs, $annotation->$key);
                    }
            }
        }

        return array_unique($imgs);
    }

    /**
	 * @param $posts
	 *
	 * @return mixed
	 */
    protected function getCardData($posts) {
        $postIds = array_map(function($post) { return $post->id; }, $posts);
        list($topicIds, $authorIds) = array_reduce($posts, function ($result, $post) {
            is_array($result) || $result = [[], []];
            /**
             * @var $post \Lychee\Bundle\CoreBundle\Entity\Post
             */
            if (!in_array($post->topicId, $result[0])) {
                $result[0][] = $post->topicId;
            }
            if (!in_array($post->authorId, $result[1])) {
                $result[1][] = $post->authorId;
            }

            return $result;
        });
        $topics = $this->topic()->fetch($topicIds);
        $authors = $this->account()->fetch($authorIds);
        $favorPosts = $this->adminFavor()->filterFavorPostIds($postIds);
        $favorIds = $this->adminFavor()->fetchFavorIds($postIds);
        $stickyPostIds = $this->postSticky()->filterStickyPostIds($postIds);
        $that = $this;
        $result = array_reduce($posts, function ($result, $post) use ($topics, $authors, $favorPosts, $that, $favorIds, $stickyPostIds) {
            /** @var $post Post */
            $postTypes = [];
            $postTypes[Post::TYPE_NORMAL] = '普通';
            $postTypes[Post::TYPE_GROUP_CHAT] = '群聊';
            $postTypes[Post::TYPE_VOTING] = '投票';
            $postTypes[Post::TYPE_SCHEDULE] = '活动';
            $postTypes[Post::TYPE_VIDEO] = '视频';
            $postTypes[Post::TYPE_SHORT_VIDEO] = '短视频';
            is_array($result) || $result = [];
            if (isset($postTypes[$post->type])) {
                $type = $postTypes[$post->type];
                $imgs = $that->getAllImages($post);
                if (count($imgs) > 0) {
                    foreach ($imgs as $img) {
                        $result[] = [
                            'id' => $post->id,
                            'isFavor' => in_array($post->id, $favorPosts)? true:false,
                            'favorId' => isset($favorIds[$post->id])? $favorIds[$post->id]:0,
                            'sticky' => in_array($post->id, $stickyPostIds)? true : false,
                            'authorId' => $post->authorId,
                            'author' => $authors[$post->authorId]->nickname,
                            'authorAvatar' => $authors[$post->authorId]->avatarUrl,
                            'type' => $type,
                            'topicId' => $post->topicId,
                            'topic' => $topics[$post->topicId]->title,
                            'imageUrl' => ImageUtility::resize($img, 240, 0),
                            'content' => $that->linkFilter($post->content),
                            'deleted' => $post->deleted,
                            'createTime' => $post->createTime->format('m-d H:i'),
                        ];
                    }
                } else {
                    $content = $post->content;
                    if ($post->type === Post::TYPE_VIDEO) {
                        $content .= ' ' . $post->videoUrl;
                    }
                    $result[] = [
                        'id' => $post->id,
                        'isFavor' => in_array($post->id, $favorPosts)? true:false,
                        'favorId' => isset($favorIds[$post->id])? $favorIds[$post->id]:0,
                        'sticky' => in_array($post->id, $stickyPostIds)? true : false,
                        'authorId' => $post->authorId,
                        'author' => $authors[$post->authorId]->nickname,
                        'authorAvatar' => $authors[$post->authorId]->avatarUrl,
                        'type' => $type,
                        'topicId' => $post->topicId,
                        'topic' => $topics[$post->topicId]->title,
                        'imageUrl' => '',
                        'content' => $that->linkFilter($content),
                        'deleted' => $post->deleted,
                        'createTime' => $post->createTime->format('m-d H:i'),
                    ];
                }
            } elseif ($post->type == Post::TYPE_RESOURCE) {
                $type = '资源';
                $resource = $that->getResource($post);
                if (!empty($resource)) {
                    $content = $post->content;
                    if (isset($resource['link'])) {
                        $title = isset($resource['title'])? $resource['title'] : '';
                        $content .= ' [' . $title . $resource['link'] . ']';
                    }
                    $result[] = [
                        'id' => $post->id,
                        'isFavor' => in_array($post->id, $favorPosts)? true:false,
                        'favorId' => isset($favorIds[$post->id])? $favorIds[$post->id]:0,
                        'sticky' => in_array($post->id, $stickyPostIds)? true : false,
                        'authorId' => $post->authorId,
                        'author' => $authors[$post->authorId]->nickname,
                        'authorAvatar' => $authors[$post->authorId]->avatarUrl,
                        'type' => $type,
                        'topicId' => $post->topicId,
                        'topic' => $topics[$post->topicId]->title,
                        'imageUrl' => isset($resource['thumb'])? ImageUtility::resize($resource['thumb'], 240, 0):'',
                        'content' => $that->linkFilter($content),
                        'deleted' => $post->deleted,
                        'createTime' => $post->createTime->format('m-d H:i'),
                    ];
                }
            }

            return $result;
        });

        return $result;
    }

    protected function fetchPostDetail($postIds) {
        $posts = $this->post()->fetch($postIds);
        list($topicIds, $authorIds) = array_reduce($posts, function($result, $post) {
            isset($result) || $result = [];
            $result[0][] = $post->topicId;
            $result[1][] = $post->authorId;

            return $result;
        });
        $topics = $this->topic()->fetch($topicIds);
        $authors = $this->account()->fetch($authorIds);
        $favors = $this->adminFavor()->filterFavorPostIds($postIds);

        return array($posts, $topics, $authors, $favors);
    }

    /**
     * @param $postIds
     * @param $imageWidth
     * @param bool $reverseOrder
     * @return array
     */
    protected function getData($postIds, $imageWidth, $reverseOrder = true) {
        list($posts, $topics, $authors, $favors) = $this->fetchPostDetail($postIds);
        $reverseOrder && krsort($posts);

        $result = [];
        foreach ($posts as $post) {
            /**
             * @var $post \Lychee\Bundle\CoreBundle\Entity\Post
             */
            if (in_array($post->id, $favors)) {
                $favor = true;
            } else {
                $favor = false;
            }
            switch($post->type) {
                case Post::TYPE_RESOURCE:
                    $type = '资源';
                    break;
                case Post::TYPE_GROUP_CHAT:
                    $type = '群聊';
                    break;
                case Post::TYPE_VOTING:
                    $type = '投票';
                    break;
                case Post::TYPE_SCHEDULE:
                    $type = '活动';
                    break;
                default:
                    $type = '普通';
            }
            if (!$post->deleted) {
                $result[] = [
                    'id' => $post->id,
                    'content' => $this->linkFilter($post->content),
                    'authorId' => $post->authorId,
                    'authorName' => $authors[$post->authorId]->nickname,
                    'type' => $type,
                    'topicId' => $post->topicId,
                    'topicName' => $topics[$post->topicId]->title,
                    'favor' => $favor,
                    'deleted' => $post->deleted,
                ];
            }
            switch ($post->type) {
                case Post::TYPE_RESOURCE:
                    $resource = $this->getResource($post);
                    if (!empty($resource) && !$post->deleted) {
                        $imageUrl = isset($resource['thumb'])? $resource['thumb'] : '';
                        if ($imageUrl && is_string($imageUrl)) {
                            $result[] = [
                                'id' => $post->id,
                                'imageUrl' => ImageUtility::resize($imageUrl, $imageWidth, 9999),
                                'content' => $post->content,
                                'favor' => $favor,
                                'deleted' => $post->deleted,
                            ];
                        }
                        $title = isset($resource['title'])? $resource['title'] : '';
                        $link = isset($resource['link'])? $resource['link'] : '';
                        $result[] = [
                            'id' => $post->id,
                            'content' => '[' . $title . '] ' . $link,
                            'type' => $type,
                            'link' => $link,
                            'favor' => $favor,
                            'deleted' => $post->deleted,
                            'authorId' => $post->authorId,
                            'authorName' => $authors[$post->authorId]->nickname,
                            'topicId' => $post->topicId,
                            'topicName' => $topics[$post->topicId]->title,
                        ];
                    }
                    break;
                default:
                    $images = $this->getAllImages($post);
                    if (!empty($images)) {
                        foreach ($images as $img) {
                            $result[] = [
                                'id' => $post->id,
                                'imageUrl' => ImageUtility::resize($img, $imageWidth, 9999),
                                'content' => $post->content,
                                'favor' => $favor,
                                'deleted' => $post->deleted,
                            ];
                        }
                    }
            }
        }

        return $result;
    }

    public function redirectErrorPage($errorMsg, Request $request, $customCallback = null) {
        if (null === $customCallback) {
            $callbackUrl = $request->headers->get('referer');
        } else {
            $callbackUrl = $customCallback;
        }
        return $this->redirect($this->generateUrl('lychee_admin_topic_error', [
            'errorMsg' => $errorMsg,
            'callbackUrl' => $callbackUrl,
        ]));
    }
} 