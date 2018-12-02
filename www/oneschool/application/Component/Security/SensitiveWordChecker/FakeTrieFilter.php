<?php
namespace Lychee\Component\Security\SensitiveWordChecker;

class FakeTrieFilter implements TrieFilterInterface {
    /**
     * @param string $path
     *
     * @return boolean
     */
    public function save($path) {
        return false;
    }

    /**
     * @param string $path
     */
    public function load($path) {
        
    }

    /**
     * @param string $word
     * @return boolean
     */
    public function addWord($word) {
        return false;
    }

    /**
     * @param array $words
     * @return int
     */
    public function addWords($words) {
        return 0;
    }

    /**
     * @param string $text
     *
     * @return array
     */
    public function search($text) {
        return array();
    }

    /**
     * @param string $text
     *
     * @return array
     */
    public function searchAll($text) {
        return array();
    }
}