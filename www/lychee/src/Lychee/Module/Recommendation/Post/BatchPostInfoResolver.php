<?php
namespace Lychee\Module\Recommendation\Post;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManager;
use Lychee\Component\Foundation\ArrayUtility;
use Lychee\Module\Post\PostService;
use Lychee\Bundle\CoreBundle\Entity\Post;

class BatchPostInfoResolver implements PostInfoResolver {

    /**
     * @var EntityManager
     */
    private $em;
    private $postIds;

    /**
     * BatchPostInfoResolver constructor.
     * @param RegistryInterface $doctrine
     * @param int[] $postIds
     */
    public function __construct($doctrine, $postIds) {
        $this->em = $doctrine->getManager();
        $this->postIds = $postIds;
    }

    private function getPostTopicCategories($postIds) {
        $conn = $this->em->getConnection();
        $stat = $conn->executeQuery('SELECT r.category_id, p.id post_id FROM post p INNER JOIN topic_category_rel r'
            .' ON p.topic_id = r.topic_id WHERE p.id IN ('.
            implode(',', $postIds) . ') AND r.category_id >= 300');
        $rows = $stat->fetchAll(\PDO::FETCH_ASSOC);
        return ArrayUtility::columns($rows, 'category_id', 'post_id');
    }

    private $postCategoryMap = null;

    /**
     * @param int $postId
     * @return int|null
     */
    public function getTopicCategoryId($postId) {
        if ($this->postCategoryMap === null) {
            $this->postCategoryMap = $this->getPostTopicCategories($this->postIds);
        }
        if (isset($this->postCategoryMap[$postId])) {
            return $this->postCategoryMap[$postId];
        } else {
            return null;
        }
    }



    private $postMap = null;
    /**
     * @param int $postId
     * @return Post|null
     */
    public function getPost($postId) {
        if ($this->postMap === null) {
            $posts = $this->em->getRepository(Post::class)->findBy(array('id' => $this->postIds));
            $result = array();
            foreach ($posts as $post) {
                $result[$post->id] = $post;
            }
            $this->postMap = $result;
        }
        if (isset($this->postMap[$postId])) {
            return $this->postMap[$postId];
        } else {
            return null;
        }
    }

    private $annotationMap = array();
    /**
     * @param int $postId
     * @return array|null
     */
    public function getPostAnnotation($postId) {
        if (isset($this->annotationMap[$postId])) {
            return $this->annotationMap[$postId];
        } else {
            $post = $this->getPost($postId);
            if ($post == null) {
                return null;
            } else {
                $a = json_decode($post->annotation, true);
                if (!is_array($a)) {
                    return null;
                } else {
                    return $a;
                }
            }
        }
    }

}