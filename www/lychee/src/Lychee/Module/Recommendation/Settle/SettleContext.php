<?php
namespace Lychee\Module\Recommendation\Settle;

class SettleContext {

    private $topicScores = [];
    private $postScores = [];
    private $postExposures = [];

    public function topicAddScore($topicId, $score) {
        if (empty($topicId) || empty($score)) {
            return;
        }
        if (isset($this->topicScores[$topicId])) {
            $this->topicScores[$topicId] += $score;
        } else {
            $this->topicScores[$topicId] = $score;
        }
    }

    public function postAddScore($postId, $score) {
        if (empty($postId) || empty($score)) {
            return;
        }
        if (isset($this->postScores[$postId])) {
            $this->postScores[$postId] += $score;
        } else {
            $this->postScores[$postId] = $score;
        }
    }

    public function postAddExposure($postId, $exposureCount) {
        if (empty($postId) || empty($exposureCount)) {
            return;
        }
        if (isset($this->postExposures[$postId])) {
            $this->postExposures[$postId] += $exposureCount;
        } else {
            $this->postExposures[$postId] = $exposureCount;
        }
    }

    public function getPostScores() {
        return $this->postScores;
    }

    public function getPostExposures() {
        return $this->postExposures;
    }

    public function getTopicScores() {
        return $this->topicScores;
    }


}