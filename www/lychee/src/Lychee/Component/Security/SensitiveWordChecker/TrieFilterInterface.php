<?php

namespace Lychee\Component\Security\SensitiveWordChecker;

interface TrieFilterInterface {
    /**
     * @param string $path
     *
     * @return mixed
     */
    public function save($path);

    /**
     * @param string $path
     */
    public function load($path);

    /**
     * @param string $word
     * @return boolean
     */
    public function addWord($word);

    /**
     * @param array $words
     * @return int
     */
    public function addWords($words);

    /**
     * @param string $text
     *
     * @return array
     */
    public function search($text);

    /**
     * @param string $text
     *
     * @return array
     */
    public function searchAll($text);
}