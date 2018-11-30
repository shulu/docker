<?php
namespace Lychee\Component\Security\SensitiveWordChecker;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class TrieSensitiveWordChecker implements SensitiveWordChecker, CacheWarmerInterface {

    /**
     * @var string
     */
    private $sensitiveWordsFilePath;

    /**
     * @var string
     */
    private $cacheDirPath;

    /**
     * @var string
     */
    private $encoding;

    /**
     * @var TrieFilterInterface
     */
    private $trieFilter;

    /**
     * @param string $sensitiveWordsFilePath
     * @param string $cacheDirPath
     * @param string $encoding mb_string encoding
     */
    public function __construct($sensitiveWordsFilePath, $cacheDirPath, $encoding = 'utf8') {
        $this->sensitiveWordsFilePath = $sensitiveWordsFilePath;
        $this->cacheDirPath = $cacheDirPath;
        $this->encoding = $encoding;
    }

    private function getTrieCachePath($cacheDir = null) {
        $cacheDir = $cacheDir ?: $this->cacheDirPath;
        return $cacheDir . DIRECTORY_SEPARATOR . 'sensitive_words.trie_tree';
    }

    /**
     * @return TrieFilter
     */
    private function getTrieFilter() {
        if ($this->trieFilter === null) {
            if (extension_loaded('trie_filter') == false) {
                $this->trieFilter = new FakeTrieFilter();
            } else if (is_readable($this->getTrieCachePath())) {
                $this->trieFilter = new TrieFilter();
                $this->trieFilter->load($this->getTrieCachePath());
            } else {
                $this->trieFilter = $this->createTrieFilterFromConfigFile();
                $this->trieFilter->save($this->getTrieCachePath());
            }
        }

        return $this->trieFilter;
    }

    /**
     * @return TrieFilter
     */
    private function createTrieFilterFromConfigFile() {
        if (extension_loaded('trie_filter') == false) {
            $trieFilter = new FakeTrieFilter();
        } else {
            $trieFilter = new TrieFilter();
        }
        $words = $this->readSensitiveWordsFromFile($this->sensitiveWordsFilePath);
        foreach ($words as $word) {
            $trieFilter->addWord($word);
        }
        return $trieFilter;
    }

    /**
     * Checks whether this warmer is optional or not.
     *
     * @return bool    true if the warmer is optional, false otherwise
     */
    public function isOptional() {
        return true;
    }

    /**
     * Warms up the cache.
     *
     * @param string $cacheDir The cache directory
     */
    public function warmUp($cacheDir) {
        $cachePath = $this->getTrieCachePath($cacheDir);
        if ($this->trieFilter === null) {
            $this->trieFilter = $this->createTrieFilterFromConfigFile();
        }
        $this->trieFilter->save($cachePath);
    }

    /**
     * @param string $path
     *
     * @return array
     */
    private function readSensitiveWordsFromFile($path) {
        return file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    /**
     * @param string $content
     *
     * @return bool
     */
    public function containSensitiveWords($content) {
        if (strlen($content) <= 0) {
            return false;
        }
        $searchResult = $this->getTrieFilter()->search($content);
        if (count($searchResult) === 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param string $content
     *
     * @return array
     */
    public function extractSensitiveWords($content) {
        if (strlen($content) <= 0) {
            return array();
        }
        $searchResult = $this->getTrieFilter()->searchAll($content);

        $result = array();
        foreach ($searchResult as $range) {
            $result[] = substr($content, $range[0], $range[1]);
        }
        return $result;
    }

    /**
     * @param string $content
     * @param string $replacementChar
     * @param int    $maxReplacementLength
     *
     * @return string
     */
    public function replaceSensitiveWords($content, $replacementChar = '*', $maxReplacementLength = 6) {
        $searchResult = $this->getTrieFilter()->searchAll($content);
        if (count($searchResult) === 0) {
            return $content;
        }

        $result = $this->replaceSearchResult(
            $content, $searchResult, $replacementChar, $maxReplacementLength
        );
        return $result;
    }

    private function mergeSearchResult($result) {
        $mergedResult = array();
        $resultIndex = 0;
        $resultCount = count($result);
        while ($resultIndex < $resultCount) {
            list($lastRangeStart, $lastRangeLength) = $result[$resultIndex];

            $indexOffset = 1;
            while ($resultIndex + $indexOffset < $resultCount) {
                list($wordStart, $wordLength) = $result[$resultIndex + $indexOffset];
                if ($lastRangeStart + $lastRangeLength < $wordStart) {
                    break;
                }
                $lastRangeLength += $wordLength;
                $indexOffset += 1;
            }

            $mergedResult[] = array($lastRangeStart, $lastRangeLength);

            $resultIndex += $indexOffset;
        }
        return $mergedResult;
    }

    private function replaceSearchResult($content, $searchResult, $replacementChar, $maxReplacementLength) {
        $mergedResult = $this->mergeSearchResult($searchResult);
        $result = $content;
        $lengthDelta = 0;
        foreach ($mergedResult as $range) {
            list($start, $length) = $range;
            $stringToReplace = substr($content, $start, $length);
            $stringToReplaceLength = mb_strlen($stringToReplace, $this->encoding);
            $replacementLength = $stringToReplaceLength > $maxReplacementLength ?
                $maxReplacementLength : $stringToReplaceLength;
            $replacementString = str_repeat($replacementChar, $replacementLength);
            $result = substr_replace($result, $replacementString, $start - $lengthDelta, $length);
            $lengthDelta += $length - $replacementLength;
        }
        return $result;
    }

} 