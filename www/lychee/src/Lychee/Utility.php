<?php
namespace Lychee;

class Utility {
    static public function buildClientTopicUrl($topicId) {
        return 'erciyuan://?action=view_topic&tid='.$topicId;
    }

    static public function buildClientUserUrl($userId) {
        return 'erciyuan://?action=view_profile&uid='.$userId;
    }

    static public function buildClientPostUrl($postId) {
        return 'erciyuan://?action=view_status&pid='.$postId;
    }

    static public function buildClientCommentUrl($postId, $commentId) {
        return 'erciyuan://?action=view_comment&pid='.$postId.'&cid='.$commentId;
    }

    static public function buildClientSubjectUrl($subjectId) {
        return 'erciyuan://?action=view_special_subject&sid='.$subjectId;
    }

    static public function buildClientSiteUrl($url) {
        return 'erciyuan://?action=open_tab&url='.urlencode($url);
    }

    static public function buildClientLiveUrl($liveId) {
        return 'erciyuan://?action=view_live&uid='.$liveId;
    }
}