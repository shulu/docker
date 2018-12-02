<?php
namespace Lychee\Component\Security\SensitiveWordChecker;

class TrieFilter implements TrieFilterInterface {
    /**
     * @var resource
     */
    private $trieTree;

    public function __construct() {

    }

    public function __destruct() {
        if ($this->trieTree) {
            trie_filter_free($this->trieTree);
        }
    }

    /**
     * @return resource
     */
    private function getTrieTree() {
        if ($this->trieTree === null) {
            $this->trieTree = trie_filter_new();
        }

        return $this->trieTree;
    }

    /**
     * @param string $path
     * @return boolean
     */
    public function save($path) {
        if ($this->trieTree) {
            return trie_filter_save($this->trieTree, $path);
        } else {
            return false;
        }
    }

    /**
     * @param string $path
     */
    public function load($path) {
        if ($this->trieTree) {
            trie_filter_free($this->trieTree);
        }
        $this->trieTree = trie_filter_load($path);
    }

    /**
     * @param string $word
     * @return boolean
     */
    public function addWord($word) {
        return trie_filter_store($this->getTrieTree(), $word);
    }

    /**
     * @param array $words
     * @return int
     */
    public function addWords($words) {
        $successCount = 0;
        foreach ($words as $word) {
            $success = $this->addWord($word);
            if ($success) {
                $successCount += 1;
            }
        }
        return $successCount;
    }

    /**
     * @param string $text
     *
     * @return array
     */
    public function search($text) {
        if (empty($text)) {
            return array();
        }
        return trie_filter_search($this->getTrieTree(), $text);
    }

    /**
     * @param string $text
     *
     * @return array
     */
    public function searchAll($text) {
        return trie_filter_search_all($this->getTrieTree(), $text);
    }

} 