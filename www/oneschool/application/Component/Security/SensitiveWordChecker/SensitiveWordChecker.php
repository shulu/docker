<?php
namespace Lychee\Component\Security\SensitiveWordChecker;

interface SensitiveWordChecker {
    /**
     * @param string $content
     *
     * @return bool
     */
    public function containSensitiveWords($content);

    /**
     * @param string $content
     *
     * @return array
     */
    public function extractSensitiveWords($content);

    /**
     * @param string $content
     * @param string $replacementChar
     * @param int    $maxReplacementLength
     *
     * @return string
     */
    public function replaceSensitiveWords($content, $replacementChar = '*', $maxReplacementLength = 6);
} 