<?php
namespace Lychee\Module\Recommendation\Post;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Lychee\Module\Post\PostService;
use Lychee\Module\Recommendation\Post\GroupManager;
use Lychee\Module\Recommendation\Post\GroupResolver;

class SettleProcessor {

    private $doctrine;
    private $postService;
    private $groupManager;

    /**
     * SettleProcessor constructor.
     * @param RegistryInterface $doctrine
     * @param GroupManager $groupManager
     */
    public function __construct($doctrine, $groupManager) {
        $this->doctrine = $doctrine;
        $this->groupManager = $groupManager;
    }

    /**
     * @param int[] $postIds
     * @return ProcessorResult
     */
    public function process($postIds) {
        $groups = $this->groupManager->getAllGroups();
        $resolvers = array();
        foreach ($groups as $g) {
            $resolvers[] = $g->resolver();
        }

        $postIdsByGroup = array();
        $offset = 0;
        gc_enable();
        do {
            $postIdsSlice = array_slice($postIds, $offset, 500, true);
            if (count($postIdsSlice) == 0) {
                break;
            }

            $pir = new BatchPostInfoResolver($this->doctrine, $postIdsSlice);
            foreach ($postIdsSlice as $postId) {
                $groupIds = $this->processOne($resolvers, $postId, $pir);
                foreach ($groupIds as $groupId) {
                    if (isset($postIdsByGroup[$groupId])) {
                        $postIdsByGroup[$groupId][] = $postId;
                    } else {
                        $postIdsByGroup[$groupId] = array($postId);
                    }
                }
            }

            $offset += 500;
            gc_collect_cycles();
        } while (true);
        return new ProcessorResult($postIdsByGroup);
    }

    /**
     * @param GroupResolver[] $resolvers
     * @param int $postId
     * @param $pir
     * @return array
     */
    private function processOne($resolvers, $postId, $pir) {
        $groupIds = array();
        foreach ($resolvers as $resolver) {
            $eachGroupIds = $resolver->resolve($postId, $pir);
            $groupIds = array_merge($groupIds, $eachGroupIds);
        }
        return array_unique($groupIds);
    }
}

class ProcessorResult implements \IteratorAggregate {

    private $postIdsByGroup;
    private $valid;

    public function __construct($postIdsByGroup) {
        $this->postIdsByGroup = $postIdsByGroup;
        $this->valid = !empty($this->postIdsByGroup);
    }

    public function getIterator() {
        return new \ArrayIterator($this->postIdsByGroup);
    }

    /**
     * @param int $groupId
     * @return int[]
     */
    public function getPostIdsOfGroup($groupId) {
        if (isset($this->postIdsByGroup[$groupId])) {
            return $this->postIdsByGroup[$groupId];
        } else {
            return array();
        }
    }
}