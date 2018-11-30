<?php
namespace Lychee\Module\Search;

use Lychee\Bundle\CoreBundle\Entity\Post;
use Lychee\Bundle\CoreBundle\Entity\TopicPost;
use Lychee\Component\Foundation\CursorableIterator\QueryCursorableIterator;
use Lychee\Module\Post\Entity\PostAudit;
use Lychee\Module\Recommendation\Entity\RecommendableTopic;

class SuperiorPostProvider extends AbstractProvider {

    protected function getClassName() {
        return Post::class;
    }

    /**
     * @return int
     */
    protected function getEntitiesCount() {
        $dql = 'SELECT count(1) FROM '.TopicPost::class.' tp
        INNER JOIN '.RecommendableTopic::class.' rt WITH rt.topicId=tp.topicId 
        LEFT JOIN '.PostAudit::class.' pa WITH pa.postId = tp.postId
        WHERE pa.status IS NULL OR pa.status=1';
        $query = $this->em->createQuery($dql);
        return intval($query->getSingleScalarResult());
    }

    /**
     * @return CursorableIterator
     */
    protected function getCursorableIterator() {
        $dql = 'SELECT p FROM '.Post::class.' p
        INNER JOIN '.TopicPost::class.' tp WITH p.id=tp.postId 
        INNER JOIN '.RecommendableTopic::class.' rt WITH rt.topicId=tp.topicId
        LEFT JOIN '.PostAudit::class.' pa WITH pa.postId = p.id
        WHERE p.id > :cursor AND p.deleted = 0 
        AND (pa.status IS NULL OR pa.status=1)
        ORDER BY p.id ASC';
        $query = $this->em->createQuery($dql);
        return new QueryCursorableIterator($query, 'id');
    }

}