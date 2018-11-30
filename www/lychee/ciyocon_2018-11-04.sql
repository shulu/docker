# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 10.66.237.195 (MySQL 5.6.28-cdb2016-log)
# Database: ciyocon
# Generation Time: 2018-11-03 16:01:29 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table activity
# ------------------------------------------------------------

DROP TABLE IF EXISTS `activity`;

CREATE TABLE `activity` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `target_id` bigint(20) NOT NULL,
  `action` smallint(6) NOT NULL,
  `create_time` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_index` (`user_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC
/*!50100 PARTITION BY RANGE (id)
(PARTITION p1 VALUES LESS THAN (849084) ENGINE = InnoDB,
 PARTITION p2 VALUES LESS THAN (10960155) ENGINE = InnoDB,
 PARTITION p3 VALUES LESS THAN MAXVALUE ENGINE = InnoDB) */;



# Dump of table activity_top_ciyo_by_week
# ------------------------------------------------------------

DROP TABLE IF EXISTS `activity_top_ciyo_by_week`;

CREATE TABLE `activity_top_ciyo_by_week` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `start_timestamp` bigint(20) NOT NULL COMMENT '开始时间',
  `end_timestamp` bigint(20) NOT NULL COMMENT '结束时间',
  `name` varchar(255) NOT NULL COMMENT '名字',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table activity_top_ciyo_by_week_videos
# ------------------------------------------------------------

DROP TABLE IF EXISTS `activity_top_ciyo_by_week_videos`;

CREATE TABLE `activity_top_ciyo_by_week_videos` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `activity_top_ciyo_by_week_id` bigint(20) NOT NULL COMMENT '每周精选次元ID',
  `video_id` bigint(20) NOT NULL COMMENT '视频ID',
  `reason_text` varchar(255) NOT NULL COMMENT '上榜理由',
  PRIMARY KEY (`id`),
  KEY `activity_top_ciyo_by_week_id` (`activity_top_ciyo_by_week_id`),
  KEY `video_id` (`video_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table admin_customize_content
# ------------------------------------------------------------

DROP TABLE IF EXISTS `admin_customize_content`;

CREATE TABLE `admin_customize_content` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `target_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table admin_daily_analysis
# ------------------------------------------------------------

DROP TABLE IF EXISTS `admin_daily_analysis`;

CREATE TABLE `admin_daily_analysis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `type` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `dailyCount` int(11) NOT NULL,
  `totalCount` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_date_idx` (`date`,`type`),
  KEY `daily_analysis_date_idx` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table admin_favor
# ------------------------------------------------------------

DROP TABLE IF EXISTS `admin_favor`;

CREATE TABLE `admin_favor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) NOT NULL,
  `create_time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table admin_gray_list
# ------------------------------------------------------------

DROP TABLE IF EXISTS `admin_gray_list`;

CREATE TABLE `admin_gray_list` (
  `post_id` bigint(20) NOT NULL,
  `creator_id` bigint(20) NOT NULL,
  `topic_id` bigint(20) NOT NULL,
  `operating_time` datetime NOT NULL,
  `manager_id` int(11) NOT NULL,
  PRIMARY KEY (`post_id`),
  KEY `creator_idx` (`creator_id`),
  KEY `topic_idx` (`topic_id`),
  KEY `operating_time_idx` (`operating_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table admin_log
# ------------------------------------------------------------

DROP TABLE IF EXISTS `admin_log`;

CREATE TABLE `admin_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operator_id` int(11) NOT NULL,
  `operation_type` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `target_id` bigint(20) NOT NULL,
  `operation_time` datetime NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table admin_manager
# ------------------------------------------------------------

DROP TABLE IF EXISTS `admin_manager`;

CREATE TABLE `admin_manager` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `salt` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `create_time` datetime NOT NULL,
  `frozen` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_3E18A730E7927C74` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table admin_manager_operation_account
# ------------------------------------------------------------

DROP TABLE IF EXISTS `admin_manager_operation_account`;

CREATE TABLE `admin_manager_operation_account` (
  `manager_id` int(11) NOT NULL,
  `operation_account_id` int(11) NOT NULL,
  PRIMARY KEY (`manager_id`,`operation_account_id`),
  KEY `IDX_61EA198D783E3463` (`manager_id`),
  KEY `IDX_61EA198DD5FC3067` (`operation_account_id`),
  CONSTRAINT `FK_61EA198D783E3463` FOREIGN KEY (`manager_id`) REFERENCES `admin_manager` (`id`),
  CONSTRAINT `FK_61EA198DD5FC3067` FOREIGN KEY (`operation_account_id`) REFERENCES `admin_operation_account` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table admin_manager_role
# ------------------------------------------------------------

DROP TABLE IF EXISTS `admin_manager_role`;

CREATE TABLE `admin_manager_role` (
  `manager_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`manager_id`,`role_id`),
  KEY `IDX_28218AFE783E3463` (`manager_id`),
  KEY `IDX_28218AFED60322AC` (`role_id`),
  CONSTRAINT `FK_28218AFE783E3463` FOREIGN KEY (`manager_id`) REFERENCES `admin_manager` (`id`),
  CONSTRAINT `FK_28218AFED60322AC` FOREIGN KEY (`role_id`) REFERENCES `admin_role` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table admin_operation_account
# ------------------------------------------------------------

DROP TABLE IF EXISTS `admin_operation_account`;

CREATE TABLE `admin_operation_account` (
  `id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table admin_recommendation_cron_job
# ------------------------------------------------------------

DROP TABLE IF EXISTS `admin_recommendation_cron_job`;

CREATE TABLE `admin_recommendation_cron_job` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recommendation_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `recommendation_id` bigint(20) NOT NULL,
  `create_time` datetime NOT NULL,
  `publish_time` datetime NOT NULL,
  `recommended_reason` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `image` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `annotation` varchar(2047) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table admin_role
# ------------------------------------------------------------

DROP TABLE IF EXISTS `admin_role`;

CREATE TABLE `admin_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `role` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `route_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_7770088A57698A6A` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table admin_tag
# ------------------------------------------------------------

DROP TABLE IF EXISTS `admin_tag`;

CREATE TABLE `admin_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `create_time` datetime NOT NULL,
  `creator_id` int(11) NOT NULL,
  `post_count` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_758EE4F65E237E06` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table admin_tag_post
# ------------------------------------------------------------

DROP TABLE IF EXISTS `admin_tag_post`;

CREATE TABLE `admin_tag_post` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `tag_id` int(11) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table admin_topics_views
# ------------------------------------------------------------

DROP TABLE IF EXISTS `admin_topics_views`;

CREATE TABLE `admin_topics_views` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `topic_id` bigint(20) NOT NULL,
  `date` date NOT NULL,
  `uni_views` int(11) NOT NULL,
  `views` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `topic_date_idx` (`topic_id`,`date`),
  KEY `date_idx` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table android_auto_update
# ------------------------------------------------------------

DROP TABLE IF EXISTS `android_auto_update`;

CREATE TABLE `android_auto_update` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_id` int(11) NOT NULL,
  `version` varchar(100) NOT NULL,
  `upload_date` datetime NOT NULL,
  `size` varchar(10) DEFAULT NULL,
  `log` varchar(500) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `auto_update` tinyint(1) NOT NULL,
  `version_code` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `app_idx` (`app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table anti_rubbish
# ------------------------------------------------------------

DROP TABLE IF EXISTS `anti_rubbish`;

CREATE TABLE `anti_rubbish` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `type` smallint(6) NOT NULL,
  `target_id` bigint(20) NOT NULL,
  `create_time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table app_channel_package
# ------------------------------------------------------------

DROP TABLE IF EXISTS `app_channel_package`;

CREATE TABLE `app_channel_package` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `link` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `update_time` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_udx` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table app_channel_title
# ------------------------------------------------------------

DROP TABLE IF EXISTS `app_channel_title`;

CREATE TABLE `app_channel_title` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_udx` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table audit_image
# ------------------------------------------------------------

DROP TABLE IF EXISTS `audit_image`;

CREATE TABLE `audit_image` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `image_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `type` smallint(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `image_post_idx` (`image_url`,`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table auth_client
# ------------------------------------------------------------

DROP TABLE IF EXISTS `auth_client`;

CREATE TABLE `auth_client` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) COLLATE utf8_unicode_ci NOT NULL,
  `create_time` datetime NOT NULL,
  `disabled` tinyint(1) NOT NULL DEFAULT '0',
  `key` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `secret` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `redirect_uri1` varchar(2083) COLLATE utf8_unicode_ci DEFAULT NULL,
  `redirect_uri2` varchar(2083) COLLATE utf8_unicode_ci DEFAULT NULL,
  `scope` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_udx` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table auth_password
# ------------------------------------------------------------

DROP TABLE IF EXISTS `auth_password`;

CREATE TABLE `auth_password` (
  `user_id` bigint(20) NOT NULL,
  `password` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `salt` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table auth_qq
# ------------------------------------------------------------

DROP TABLE IF EXISTS `auth_qq`;

CREATE TABLE `auth_qq` (
  `open_id` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `user_id` bigint(20) NOT NULL,
  PRIMARY KEY (`open_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table auth_sina_weibo
# ------------------------------------------------------------

DROP TABLE IF EXISTS `auth_sina_weibo`;

CREATE TABLE `auth_sina_weibo` (
  `weibo_uid` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  PRIMARY KEY (`weibo_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table auth_token
# ------------------------------------------------------------

DROP TABLE IF EXISTS `auth_token`;

CREATE TABLE `auth_token` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `access_token` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `client_id` int(11) NOT NULL,
  `scope` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `grant_type` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `create_time` int(11) NOT NULL,
  `ttl` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `access_token_udx` (`access_token`),
  KEY `user_id_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table auth_wechat
# ------------------------------------------------------------

DROP TABLE IF EXISTS `auth_wechat`;

CREATE TABLE `auth_wechat` (
  `open_id` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` bigint(20) NOT NULL,
  PRIMARY KEY (`open_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table blocking_device
# ------------------------------------------------------------

DROP TABLE IF EXISTS `blocking_device`;

CREATE TABLE `blocking_device` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `device_id` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `create_time` datetime NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_device_id_udx` (`platform`,`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table blocking_topic
# ------------------------------------------------------------

DROP TABLE IF EXISTS `blocking_topic`;

CREATE TABLE `blocking_topic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `channel` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `version` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `topics` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `channel_version_idx` (`channel`,`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table caitu_record
# ------------------------------------------------------------

DROP TABLE IF EXISTS `caitu_record`;

CREATE TABLE `caitu_record` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `phone` varchar(20) NOT NULL,
  `ddate` datetime NOT NULL,
  `tdate` datetime NOT NULL,
  `extra` varchar(255) DEFAULT NULL,
  `state` smallint(6) DEFAULT NULL,
  `fee` decimal(20,2) NOT NULL,
  `type` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone_udx` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table campaign
# ------------------------------------------------------------

DROP TABLE IF EXISTS `campaign`;

CREATE TABLE `campaign` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `image` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `link` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `views` int(11) NOT NULL DEFAULT '-1',
  `unique_views` int(11) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table campaign_topic
# ------------------------------------------------------------

DROP TABLE IF EXISTS `campaign_topic`;

CREATE TABLE `campaign_topic` (
  `campaign_id` bigint(20) NOT NULL,
  `topic_id` bigint(20) NOT NULL,
  `position` smallint(6) NOT NULL,
  PRIMARY KEY (`campaign_id`,`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table captured_site_content
# ------------------------------------------------------------

DROP TABLE IF EXISTS `captured_site_content`;

CREATE TABLE `captured_site_content` (
  `site_name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `content_id` bigint(20) NOT NULL,
  PRIMARY KEY (`site_name`,`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table city
# ------------------------------------------------------------

DROP TABLE IF EXISTS `city`;

CREATE TABLE `city` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table city_post
# ------------------------------------------------------------

DROP TABLE IF EXISTS `city_post`;

CREATE TABLE `city_post` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `city_id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `author_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `city_id` (`city_id`),
  KEY `author_id` (`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table cms_setting
# ------------------------------------------------------------

DROP TABLE IF EXISTS `cms_setting`;

CREATE TABLE `cms_setting` (
  `key` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `value` longtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table column
# ------------------------------------------------------------

DROP TABLE IF EXISTS `column`;

CREATE TABLE `column` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `create_time` datetime NOT NULL,
  `deleted` tinyint(1) NOT NULL,
  `published` tinyint(1) NOT NULL,
  `order` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table column_element
# ------------------------------------------------------------

DROP TABLE IF EXISTS `column_element`;

CREATE TABLE `column_element` (
  `column_id` int(11) NOT NULL,
  `element_id` bigint(20) NOT NULL,
  `create_time` datetime NOT NULL,
  `recommendation_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order` int(11) NOT NULL,
  `image_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`column_id`,`element_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table comment
# ------------------------------------------------------------

DROP TABLE IF EXISTS `comment`;

CREATE TABLE `comment` (
  `id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `author_id` bigint(20) NOT NULL,
  `replied_id` bigint(20) DEFAULT NULL,
  `create_time` datetime NOT NULL,
  `ip` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `district` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_url` varchar(2083) COLLATE utf8_unicode_ci DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL,
  `liked_count` int(11) NOT NULL,
  `annotation` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `post_id_liked_count_index` (`post_id`,`deleted`,`liked_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table domain_whitelist
# ------------------------------------------------------------

DROP TABLE IF EXISTS `domain_whitelist`;

CREATE TABLE `domain_whitelist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `domain` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `type` smallint(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `domain_udx` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table editor_choice_topic
# ------------------------------------------------------------

DROP TABLE IF EXISTS `editor_choice_topic`;

CREATE TABLE `editor_choice_topic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `topic_id` bigint(20) NOT NULL,
  `position` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table editor_choice_topic_category
# ------------------------------------------------------------

DROP TABLE IF EXISTS `editor_choice_topic_category`;

CREATE TABLE `editor_choice_topic_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table export_unactivited_users_20180723
# ------------------------------------------------------------

DROP TABLE IF EXISTS `export_unactivited_users_20180723`;

CREATE TABLE `export_unactivited_users_20180723` (
  `user_id` bigint(20) NOT NULL,
  `nickname` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `post_count_1` int(11) NOT NULL COMMENT '20180609-20180622发帖数',
  `like_count_1` int(11) DEFAULT NULL COMMENT '20180609-20180622点赞数',
  `comment_count_1` int(11) DEFAULT NULL COMMENT '20180609-20180622评论数',
  `post_count_2` int(11) NOT NULL COMMENT '20180623至今发帖数',
  `like_count_2` int(11) DEFAULT NULL COMMENT '20180623至今点赞数',
  `comment_count_2` int(11) DEFAULT NULL COMMENT '20180623至今评论数',
  `follower_count_1` int(11) DEFAULT NULL COMMENT '20180609-20180622粉丝数',
  `phone` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `reg_time` datetime NOT NULL COMMENT 'user.create_time',
  `post_count` int(11) NOT NULL DEFAULT '0',
  `frozen` tinyint(1) NOT NULL DEFAULT '0',
  `last_login_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `auth_login_type` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `is_post_in_kaiche_topic` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否在开车次元发过贴',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='导出非活跃老用户20180723';



# Dump of table expression
# ------------------------------------------------------------

DROP TABLE IF EXISTS `expression`;

CREATE TABLE `expression` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `image_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `filename` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table expression_package
# ------------------------------------------------------------

DROP TABLE IF EXISTS `expression_package`;

CREATE TABLE `expression_package` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `cover_image` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `create_time` datetime NOT NULL,
  `last_modified_time` datetime NOT NULL,
  `deleted` tinyint(1) NOT NULL,
  `downloadUrl` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table expression_package_version
# ------------------------------------------------------------

DROP TABLE IF EXISTS `expression_package_version`;

CREATE TABLE `expression_package_version` (
  `version` int(11) NOT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table game
# ------------------------------------------------------------

DROP TABLE IF EXISTS `game`;

CREATE TABLE `game` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `app_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `create_time` datetime NOT NULL,
  `description` varchar(1000) COLLATE utf8_unicode_ci NOT NULL,
  `banner` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `icon` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `app_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ios_size` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `android_size` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ios_link` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `android_link` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `app_screenshots` varchar(2083) COLLATE utf8_unicode_ci NOT NULL,
  `topic_id` bigint(20) DEFAULT NULL,
  `short_description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `category_id` smallint(6) DEFAULT NULL,
  `publisher` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `player_numbers` bigint(20) DEFAULT '0',
  `launch_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `category_idx` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table game_banner
# ------------------------------------------------------------

DROP TABLE IF EXISTS `game_banner`;

CREATE TABLE `game_banner` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `position` int(11) DEFAULT NULL,
  `url` varchar(2083) COLLATE utf8_unicode_ci NOT NULL,
  `image_url` varchar(2083) COLLATE utf8_unicode_ci NOT NULL,
  `image_width` int(11) NOT NULL,
  `image_height` int(11) NOT NULL,
  `title` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `share_title` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `share_text` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `share_image_url` varchar(2083) COLLATE utf8_unicode_ci DEFAULT NULL,
  `share_big_image_url` varchar(2083) COLLATE utf8_unicode_ci DEFAULT NULL,
  `views` int(11) NOT NULL DEFAULT '0',
  `unique_views` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table game_category
# ------------------------------------------------------------

DROP TABLE IF EXISTS `game_category`;

CREATE TABLE `game_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `icon` varchar(2083) DEFAULT NULL,
  `name` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table game_columns
# ------------------------------------------------------------

DROP TABLE IF EXISTS `game_columns`;

CREATE TABLE `game_columns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table game_columns_recommendation
# ------------------------------------------------------------

DROP TABLE IF EXISTS `game_columns_recommendation`;

CREATE TABLE `game_columns_recommendation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `column_id` int(11) NOT NULL,
  `game_id` bigint(20) NOT NULL,
  `position` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `columns_idx` (`column_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table image_review
# ------------------------------------------------------------

DROP TABLE IF EXISTS `image_review`;

CREATE TABLE `image_review` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `image` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `label` smallint(6) NOT NULL,
  `review` tinyint(1) NOT NULL,
  `rate` double NOT NULL,
  `last_review_time` datetime NOT NULL,
  `review_result` smallint(6) NOT NULL,
  `review_source` smallint(6) NOT NULL DEFAULT '1' COMMENT '审核处理来源',
  PRIMARY KEY (`id`),
  UNIQUE KEY `image_idx` (`image`),
  KEY `last_review_time_idx` (`last_review_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table image_review_audit_config
# ------------------------------------------------------------

DROP TABLE IF EXISTS `image_review_audit_config`;

CREATE TABLE `image_review_audit_config` (
  `id` bigint(20) unsigned NOT NULL,
  `title` varchar(60) NOT NULL,
  `value` varchar(255) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `update_time` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table image_review_source
# ------------------------------------------------------------

DROP TABLE IF EXISTS `image_review_source`;

CREATE TABLE `image_review_source` (
  `review_id` bigint(20) NOT NULL,
  `source_type` smallint(6) NOT NULL,
  `source_id` bigint(20) NOT NULL,
  PRIMARY KEY (`review_id`,`source_type`,`source_id`),
  KEY `source_idx` (`source_type`,`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table input_domain
# ------------------------------------------------------------

DROP TABLE IF EXISTS `input_domain`;

CREATE TABLE `input_domain` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `count` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `domain_name_idx` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table input_domain_daily_record
# ------------------------------------------------------------

DROP TABLE IF EXISTS `input_domain_daily_record`;

CREATE TABLE `input_domain_daily_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `domain_id` int(11) NOT NULL,
  `count` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table input_domain_record
# ------------------------------------------------------------

DROP TABLE IF EXISTS `input_domain_record`;

CREATE TABLE `input_domain_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datetime` datetime NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `domain_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table ip_blocker_records
# ------------------------------------------------------------

DROP TABLE IF EXISTS `ip_blocker_records`;

CREATE TABLE `ip_blocker_records` (
  `ip` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `action` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `last_time` int(11) NOT NULL,
  `day_count` int(11) NOT NULL,
  `hour_count` smallint(6) NOT NULL,
  `version` bigint(20) NOT NULL,
  PRIMARY KEY (`ip`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table like_comment
# ------------------------------------------------------------

DROP TABLE IF EXISTS `like_comment`;

CREATE TABLE `like_comment` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `liker_id` bigint(20) NOT NULL,
  `comment_id` bigint(20) NOT NULL,
  `state` smallint(6) NOT NULL,
  `update_time` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `liker_comment_state_udx` (`liker_id`,`comment_id`,`state`),
  UNIQUE KEY `comment_state_id_udx` (`comment_id`,`state`,`id`),
  KEY `update_time_idx` (`update_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table like_post
# ------------------------------------------------------------

DROP TABLE IF EXISTS `like_post`;

CREATE TABLE `like_post` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `liker_id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `state` smallint(6) NOT NULL,
  `update_time` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `liker_post_state_udx` (`liker_id`,`post_id`,`state`),
  UNIQUE KEY `post_state_id_udx` (`post_id`,`state`,`id`),
  KEY `update_time_idx` (`update_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table like_post_period_count
# ------------------------------------------------------------

DROP TABLE IF EXISTS `like_post_period_count`;

CREATE TABLE `like_post_period_count` (
  `post_id` bigint(20) unsigned NOT NULL COMMENT '帖子id',
  `first_id` bigint(20) unsigned NOT NULL COMMENT '统计时的第一条点赞流水id',
  `last_id` bigint(20) unsigned NOT NULL COMMENT '统计时的最后一条点赞流水id',
  `count` bigint(20) unsigned NOT NULL COMMENT '点赞数',
  PRIMARY KEY (`post_id`),
  KEY `last_id` (`last_id`),
  KEY `post_id_count` (`post_id`,`count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table live_post
# ------------------------------------------------------------

DROP TABLE IF EXISTS `live_post`;

CREATE TABLE `live_post` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `author_id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `start_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `finish` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table notification_counting
# ------------------------------------------------------------

DROP TABLE IF EXISTS `notification_counting`;

CREATE TABLE `notification_counting` (
  `user_id` bigint(20) NOT NULL,
  `likes_unread` int(11) NOT NULL DEFAULT '0',
  `events_unread` int(11) NOT NULL DEFAULT '0',
  `official_cursor` bigint(20) NOT NULL DEFAULT '0',
  `comments_unread` int(11) NOT NULL DEFAULT '0',
  `topics_unread` int(11) NOT NULL DEFAULT '0',
  `mentions_unread` int(11) NOT NULL DEFAULT '0',
  `announcements_unread` int(11) NOT NULL DEFAULT '0',
  `no_topic_unread` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table notification_event
# ------------------------------------------------------------

DROP TABLE IF EXISTS `notification_event`;

CREATE TABLE `notification_event` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `actor_id` bigint(20) NOT NULL,
  `type` smallint(6) NOT NULL,
  `target_id` bigint(20) DEFAULT NULL,
  `create_time` datetime NOT NULL,
  `message` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_index` (`user_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table notification_group_event
# ------------------------------------------------------------

DROP TABLE IF EXISTS `notification_group_event`;

CREATE TABLE `notification_group_event` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `group_type` smallint(6) DEFAULT NULL,
  `topic_id` bigint(20) DEFAULT NULL,
  `actor_id` bigint(20) NOT NULL,
  `type` smallint(6) NOT NULL,
  `target_id` bigint(20) DEFAULT NULL,
  `create_time` datetime NOT NULL,
  `message` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_index` (`user_id`,`id`),
  KEY `user_group_id_index` (`user_id`,`group_type`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table notification_like
# ------------------------------------------------------------

DROP TABLE IF EXISTS `notification_like`;

CREATE TABLE `notification_like` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `liker_id` bigint(20) NOT NULL,
  `type` smallint(6) NOT NULL,
  `likee_id` bigint(20) NOT NULL,
  `create_time` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_index` (`user_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table notification_official
# ------------------------------------------------------------

DROP TABLE IF EXISTS `notification_official`;

CREATE TABLE `notification_official` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `from_id` bigint(20) NOT NULL,
  `create_time` datetime NOT NULL,
  `type` smallint(6) NOT NULL,
  `target_id` bigint(20) DEFAULT NULL,
  `url` varchar(2083) COLLATE utf8_unicode_ci DEFAULT NULL,
  `image` varchar(2083) COLLATE utf8_unicode_ci DEFAULT NULL,
  `message` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `publish_time` datetime NOT NULL,
  `views` int(11) NOT NULL DEFAULT '0',
  `unique_views` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `publish_time_idx` (`publish_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table notification_official_push
# ------------------------------------------------------------

DROP TABLE IF EXISTS `notification_official_push`;

CREATE TABLE `notification_official_push` (
  `notification_id` bigint(20) NOT NULL,
  `message` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `push_time` datetime NOT NULL,
  `platform` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `pushed` smallint(6) NOT NULL,
  `tags` varchar(1023) COLLATE utf8_unicode_ci DEFAULT NULL,
  `next_push_time` datetime NOT NULL,
  PRIMARY KEY (`notification_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table notification_push_setting
# ------------------------------------------------------------

DROP TABLE IF EXISTS `notification_push_setting`;

CREATE TABLE `notification_push_setting` (
  `user_id` bigint(20) NOT NULL,
  `no_disturb` tinyint(1) NOT NULL,
  `no_disturb_timezone` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `no_disturb_start_time` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `no_disturb_end_time` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mention_me` smallint(6) NOT NULL DEFAULT '1',
  `comment_me` smallint(6) NOT NULL DEFAULT '1',
  `image_comment_me` smallint(6) NOT NULL DEFAULT '1',
  `follow_me` smallint(6) NOT NULL DEFAULT '1',
  `like_me` smallint(6) NOT NULL DEFAULT '1',
  `message_me` smallint(6) NOT NULL DEFAULT '1',
  `topic_apply` smallint(6) NOT NULL DEFAULT '1',
  `schedule` smallint(6) NOT NULL DEFAULT '1',
  `followee_post` smallint(6) NOT NULL DEFAULT '2',
  `followee_anchor` smallint(6) NOT NULL DEFAULT '1',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table notification_topic_counting
# ------------------------------------------------------------

DROP TABLE IF EXISTS `notification_topic_counting`;

CREATE TABLE `notification_topic_counting` (
  `user_id` bigint(20) NOT NULL,
  `topic_id` bigint(20) NOT NULL,
  `events_unread` int(11) NOT NULL,
  `likes_unread` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table notification_topic_event
# ------------------------------------------------------------

DROP TABLE IF EXISTS `notification_topic_event`;

CREATE TABLE `notification_topic_event` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `topic_id` bigint(20) DEFAULT NULL,
  `actor_id` bigint(20) NOT NULL,
  `type` smallint(6) NOT NULL,
  `target_id` bigint(20) DEFAULT NULL,
  `create_time` datetime NOT NULL,
  `message` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_index` (`user_id`,`id`),
  KEY `user_topic_id_idx` (`user_id`,`topic_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table notification_topic_like
# ------------------------------------------------------------

DROP TABLE IF EXISTS `notification_topic_like`;

CREATE TABLE `notification_topic_like` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `topic_id` bigint(20) DEFAULT NULL,
  `liker_id` bigint(20) NOT NULL,
  `type` smallint(6) NOT NULL,
  `likee_id` bigint(20) NOT NULL,
  `create_time` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_index` (`user_id`,`id`),
  KEY `user_topic_id_idx` (`user_id`,`topic_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table phone_code
# ------------------------------------------------------------

DROP TABLE IF EXISTS `phone_code`;

CREATE TABLE `phone_code` (
  `area_code` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `create_time` int(11) NOT NULL,
  `code` varchar(6) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`area_code`,`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table post
# ------------------------------------------------------------

DROP TABLE IF EXISTS `post`;

CREATE TABLE `post` (
  `id` bigint(20) NOT NULL,
  `author_id` bigint(20) NOT NULL,
  `reposted_id` bigint(20) DEFAULT NULL,
  `topic_id` bigint(20) DEFAULT NULL,
  `create_time` datetime NOT NULL,
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(2083) COLLATE utf8_unicode_ci DEFAULT NULL,
  `video_url` varchar(2083) COLLATE utf8_unicode_ci DEFAULT NULL,
  `audio_url` varchar(2083) COLLATE utf8_unicode_ci DEFAULT NULL,
  `site_url` varchar(2083) COLLATE utf8_unicode_ci DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `address` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL,
  `news_source` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `annotation` varchar(2048) COLLATE utf8_unicode_ci DEFAULT NULL,
  `folded` tinyint(1) NOT NULL,
  `type` smallint(6) NOT NULL DEFAULT '0',
  `sticky_level` smallint(6) NOT NULL DEFAULT '0',
  `im_group_id` bigint(20) DEFAULT NULL,
  `schedule_id` bigint(20) DEFAULT NULL,
  `voting_id` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `create_time_index` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table post_audit
# ------------------------------------------------------------

DROP TABLE IF EXISTS `post_audit`;

CREATE TABLE `post_audit` (
  `post_id` bigint(20) unsigned NOT NULL,
  `status` smallint(5) unsigned NOT NULL DEFAULT '3' COMMENT '1：审核通过，2：审核不通过，3：未审核',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  `source` smallint(5) unsigned NOT NULL DEFAULT '1' COMMENT '审核来源',
  PRIMARY KEY (`post_id`),
  KEY `status_update_time` (`status`,`update_time`),
  KEY `status_source_update_time` (`status`,`source`,`update_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table post_audit_config
# ------------------------------------------------------------

DROP TABLE IF EXISTS `post_audit_config`;

CREATE TABLE `post_audit_config` (
  `id` bigint(20) unsigned NOT NULL,
  `title` varchar(60) NOT NULL,
  `value` varchar(255) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `update_time` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table post_audit_limit_topic
# ------------------------------------------------------------

DROP TABLE IF EXISTS `post_audit_limit_topic`;

CREATE TABLE `post_audit_limit_topic` (
  `topic_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table post_category_score
# ------------------------------------------------------------

DROP TABLE IF EXISTS `post_category_score`;

CREATE TABLE `post_category_score` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `only_category` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pid_udx` (`post_id`),
  KEY `cid_id_idx` (`category_id`,`id`),
  KEY `cid_score_idx` (`category_id`,`score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table post_comment
# ------------------------------------------------------------

DROP TABLE IF EXISTS `post_comment`;

CREATE TABLE `post_comment` (
  `post_id` bigint(20) NOT NULL,
  `comment_id` bigint(20) NOT NULL,
  `has_image` tinyint(1) NOT NULL,
  PRIMARY KEY (`post_id`,`comment_id`),
  KEY `post_image_comment_idx` (`post_id`,`has_image`,`comment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table post_comment_image
# ------------------------------------------------------------

DROP TABLE IF EXISTS `post_comment_image`;

CREATE TABLE `post_comment_image` (
  `post_id` bigint(20) NOT NULL,
  `comment_id` bigint(20) NOT NULL,
  PRIMARY KEY (`post_id`,`comment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table post_counting
# ------------------------------------------------------------

DROP TABLE IF EXISTS `post_counting`;

CREATE TABLE `post_counting` (
  `post_id` bigint(20) NOT NULL,
  `liked_count` int(11) NOT NULL,
  `commented_count` int(11) NOT NULL,
  `reposted_count` int(11) NOT NULL,
  PRIMARY KEY (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table post_exposure_records
# ------------------------------------------------------------

DROP TABLE IF EXISTS `post_exposure_records`;

CREATE TABLE `post_exposure_records` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `time` int(11) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `topic_id` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table post_extra
# ------------------------------------------------------------

DROP TABLE IF EXISTS `post_extra`;

CREATE TABLE `post_extra` (
  `id` bigint(20) unsigned NOT NULL COMMENT 'post.id',
  `author_id` bigint(20) unsigned NOT NULL COMMENT 'post.author_id,帖子主人',
  `topic_id` bigint(20) unsigned DEFAULT NULL COMMENT 'post.topic_id,帖子的次元ID',
  `type` smallint(6) NOT NULL DEFAULT '0' COMMENT 'post.type,帖子类型',
  PRIMARY KEY (`id`),
  KEY `user_type` (`author_id`,`type`),
  KEY `topic_id_type` (`topic_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='帖子扩展表，冗余id，主人，次元id，类型。用于排序';



# Dump of table post_resource_title
# ------------------------------------------------------------

DROP TABLE IF EXISTS `post_resource_title`;

CREATE TABLE `post_resource_title` (
  `post_id` bigint(20) NOT NULL,
  `content` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `url` varchar(2083) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table promotion_banner
# ------------------------------------------------------------

DROP TABLE IF EXISTS `promotion_banner`;

CREATE TABLE `promotion_banner` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `position` int(11) DEFAULT NULL,
  `url` varchar(2083) COLLATE utf8_unicode_ci NOT NULL,
  `image_url` varchar(2083) COLLATE utf8_unicode_ci NOT NULL,
  `image_width` int(11) NOT NULL,
  `image_height` int(11) NOT NULL,
  `title` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `share_title` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `share_text` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `share_image_url` varchar(2083) COLLATE utf8_unicode_ci DEFAULT NULL,
  `share_big_image_url` varchar(2083) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table qingdian_user
# ------------------------------------------------------------

DROP TABLE IF EXISTS `qingdian_user`;

CREATE TABLE `qingdian_user` (
  `user_id` bigint(20) NOT NULL,
  `request_time` datetime NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table rec_group_posts
# ------------------------------------------------------------

DROP TABLE IF EXISTS `rec_group_posts`;

CREATE TABLE `rec_group_posts` (
  `seq_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  PRIMARY KEY (`seq_id`),
  UNIQUE KEY `gid_pid_udx` (`group_id`,`post_id`),
  KEY `gid_sid_idx` (`group_id`,`seq_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table recommendable_topic
# ------------------------------------------------------------

DROP TABLE IF EXISTS `recommendable_topic`;

CREATE TABLE `recommendable_topic` (
  `topic_id` bigint(20) NOT NULL,
  PRIMARY KEY (`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table recommendation_banner
# ------------------------------------------------------------

DROP TABLE IF EXISTS `recommendation_banner`;

CREATE TABLE `recommendation_banner` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `position` int(11) DEFAULT NULL,
  `url` varchar(2083) COLLATE utf8_unicode_ci NOT NULL,
  `image_url` varchar(2083) COLLATE utf8_unicode_ci NOT NULL,
  `image_width` int(11) NOT NULL,
  `image_height` int(11) NOT NULL,
  `title` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `share_title` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `share_text` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `share_image_url` varchar(2083) COLLATE utf8_unicode_ci DEFAULT NULL,
  `share_big_image_url` varchar(2083) COLLATE utf8_unicode_ci DEFAULT NULL,
  `views` int(11) NOT NULL DEFAULT '0',
  `unique_views` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table recommendation_group
# ------------------------------------------------------------

DROP TABLE IF EXISTS `recommendation_group`;

CREATE TABLE `recommendation_group` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `type` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table recommendation_item
# ------------------------------------------------------------

DROP TABLE IF EXISTS `recommendation_item`;

CREATE TABLE `recommendation_item` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `target_id` bigint(20) NOT NULL,
  `reason` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `image` varchar(2083) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `create_time` datetime NOT NULL,
  `sticky` smallint(6) NOT NULL DEFAULT '0',
  `position` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table recommendation_subbanner
# ------------------------------------------------------------

DROP TABLE IF EXISTS `recommendation_subbanner`;

CREATE TABLE `recommendation_subbanner` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `target_id` bigint(20) NOT NULL,
  `position` smallint(6) NOT NULL,
  `image_url` varchar(2014) COLLATE utf8_unicode_ci NOT NULL,
  `create_time` datetime NOT NULL,
  `deleted` tinyint(1) NOT NULL,
  `published` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table recommendation_topic_test
# ------------------------------------------------------------

DROP TABLE IF EXISTS `recommendation_topic_test`;

CREATE TABLE `recommendation_topic_test` (
  `property` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `score` smallint(6) NOT NULL,
  `topic_id` bigint(20) NOT NULL,
  PRIMARY KEY (`property`,`score`,`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table report
# ------------------------------------------------------------

DROP TABLE IF EXISTS `report`;

CREATE TABLE `report` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reporter_id` bigint(20) NOT NULL,
  `type` smallint(6) NOT NULL,
  `subject_id` bigint(20) NOT NULL,
  `time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table review_state
# ------------------------------------------------------------

DROP TABLE IF EXISTS `review_state`;

CREATE TABLE `review_state` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `channel` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `version` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `in_review` tinyint(1) NOT NULL,
  `app_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_channel_idx` (`app_id`,`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table robot
# ------------------------------------------------------------

DROP TABLE IF EXISTS `robot`;

CREATE TABLE `robot` (
  `id` bigint(20) unsigned NOT NULL COMMENT '用户id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table robot_comment
# ------------------------------------------------------------

DROP TABLE IF EXISTS `robot_comment`;

CREATE TABLE `robot_comment` (
  `id` bigint(20) unsigned NOT NULL COMMENT '评论id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table robot_comment_task
# ------------------------------------------------------------

DROP TABLE IF EXISTS `robot_comment_task`;

CREATE TABLE `robot_comment_task` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `total` bigint(20) unsigned NOT NULL,
  `target_id` bigint(20) unsigned NOT NULL COMMENT '要评论的帖子id',
  `state` smallint(6) NOT NULL COMMENT '任务当前状态, 1：未处理，2：处理中，3：已处理',
  `update_time` int(11) NOT NULL COMMENT '任务状态更新时间',
  `create_time` int(11) NOT NULL COMMENT '任务创建时间',
  PRIMARY KEY (`id`),
  KEY `state_create_time_idx` (`state`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table robot_commentator
# ------------------------------------------------------------

DROP TABLE IF EXISTS `robot_commentator`;

CREATE TABLE `robot_commentator` (
  `id` bigint(20) unsigned NOT NULL COMMENT '点赞用户id',
  `total` bigint(20) unsigned NOT NULL COMMENT '点赞次数',
  `action_time` int(11) NOT NULL COMMENT '最后一次操作时间',
  PRIMARY KEY (`id`),
  KEY `action_time_total_idx` (`action_time`,`total`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table robot_like_post
# ------------------------------------------------------------

DROP TABLE IF EXISTS `robot_like_post`;

CREATE TABLE `robot_like_post` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `liker_id` bigint(20) unsigned NOT NULL COMMENT '点赞用户id',
  `post_id` bigint(20) unsigned NOT NULL COMMENT '帖子id',
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `liker_post_udx` (`liker_id`,`post_id`),
  KEY `time_idx` (`time`),
  KEY `post_liker_idx` (`post_id`,`liker_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table robot_like_post_task
# ------------------------------------------------------------

DROP TABLE IF EXISTS `robot_like_post_task`;

CREATE TABLE `robot_like_post_task` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `total` bigint(20) unsigned NOT NULL,
  `post_id` bigint(20) unsigned NOT NULL,
  `state` smallint(6) NOT NULL COMMENT '任务当前状态, 1：未处理，2：处理中，3：已处理',
  `update_time` int(11) NOT NULL COMMENT '任务状态更新时间',
  `create_time` int(11) NOT NULL COMMENT '任务创建时间',
  PRIMARY KEY (`id`),
  KEY `state_create_time_idx` (`state`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table robot_post_liker
# ------------------------------------------------------------

DROP TABLE IF EXISTS `robot_post_liker`;

CREATE TABLE `robot_post_liker` (
  `id` bigint(20) unsigned NOT NULL COMMENT '点赞用户id',
  `total` bigint(20) unsigned NOT NULL COMMENT '点赞次数',
  `action_time` int(11) NOT NULL COMMENT '最后一次操作时间',
  PRIMARY KEY (`id`),
  KEY `action_time_total_idx` (`action_time`,`total`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table robot_user_follow_task
# ------------------------------------------------------------

DROP TABLE IF EXISTS `robot_user_follow_task`;

CREATE TABLE `robot_user_follow_task` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `total` bigint(20) unsigned NOT NULL,
  `target_id` bigint(20) unsigned NOT NULL,
  `state` smallint(6) NOT NULL COMMENT '任务当前状态, 1：未处理，2：处理中，3：已处理',
  `update_time` int(11) NOT NULL COMMENT '任务状态更新时间',
  `create_time` int(11) NOT NULL COMMENT '任务创建时间',
  PRIMARY KEY (`id`),
  KEY `state_create_time_idx` (`state`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table robot_user_follower
# ------------------------------------------------------------

DROP TABLE IF EXISTS `robot_user_follower`;

CREATE TABLE `robot_user_follower` (
  `id` bigint(20) unsigned NOT NULL COMMENT '点赞用户id',
  `total` bigint(20) unsigned NOT NULL COMMENT '点赞次数',
  `action_time` int(11) NOT NULL COMMENT '最后一次操作时间',
  PRIMARY KEY (`id`),
  KEY `action_time_total_idx` (`action_time`,`total`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table robot_user_following
# ------------------------------------------------------------

DROP TABLE IF EXISTS `robot_user_following`;

CREATE TABLE `robot_user_following` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `follower_id` bigint(20) unsigned NOT NULL COMMENT '点赞用户id',
  `followee_id` bigint(20) unsigned NOT NULL COMMENT '帖子id',
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `follower_followee__udx` (`follower_id`,`followee_id`),
  KEY `time_idx` (`time`),
  KEY `followee_follower_idx` (`followee_id`,`follower_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table schedule
# ------------------------------------------------------------

DROP TABLE IF EXISTS `schedule`;

CREATE TABLE `schedule` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `creator_id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `name` varchar(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` varchar(400) COLLATE utf8_unicode_ci DEFAULT NULL,
  `address` varchar(400) COLLATE utf8_unicode_ci DEFAULT NULL,
  `poi` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `cancelled` tinyint(1) NOT NULL,
  `canceller_id` bigint(20) DEFAULT NULL,
  `joiner_count` int(11) NOT NULL,
  `last_notify_time` int(11) DEFAULT NULL,
  `topic_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `starttime_idx` (`start_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table schedule_by_topic
# ------------------------------------------------------------

DROP TABLE IF EXISTS `schedule_by_topic`;

CREATE TABLE `schedule_by_topic` (
  `topic_id` bigint(20) NOT NULL,
  `schedule_id` bigint(20) NOT NULL,
  `start_time` int(11) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  PRIMARY KEY (`topic_id`,`schedule_id`),
  UNIQUE KEY `topic_starttime_schedule_udx` (`topic_id`,`start_time`,`schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table schedule_by_user
# ------------------------------------------------------------

DROP TABLE IF EXISTS `schedule_by_user`;

CREATE TABLE `schedule_by_user` (
  `user_id` bigint(20) NOT NULL,
  `schedule_id` bigint(20) NOT NULL,
  `start_time` int(11) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`,`schedule_id`),
  UNIQUE KEY `user_starttime_schedule_udx` (`user_id`,`start_time`,`schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table schedule_joiners
# ------------------------------------------------------------

DROP TABLE IF EXISTS `schedule_joiners`;

CREATE TABLE `schedule_joiners` (
  `schedule_id` bigint(20) NOT NULL,
  `joiner_id` bigint(20) NOT NULL,
  `position` bigint(20) NOT NULL,
  PRIMARY KEY (`schedule_id`,`joiner_id`),
  UNIQUE KEY `schedule_position_udx` (`schedule_id`,`position`,`joiner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table search_keyword
# ------------------------------------------------------------

DROP TABLE IF EXISTS `search_keyword`;

CREATE TABLE `search_keyword` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(255) CHARACTER SET utf8mb4 NOT NULL,
  `create_time` datetime NOT NULL,
  `last_record_time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table search_record
# ------------------------------------------------------------

DROP TABLE IF EXISTS `search_record`;

CREATE TABLE `search_record` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `keyword_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `record_time` datetime NOT NULL,
  `search_type` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table sms_records
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sms_records`;

CREATE TABLE `sms_records` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `time` datetime NOT NULL,
  `ip` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `area_code` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `platform` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `os_version` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `app_version` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `device_id` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table spammer_records
# ------------------------------------------------------------

DROP TABLE IF EXISTS `spammer_records`;

CREATE TABLE `spammer_records` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `spammer_id` bigint(20) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_B029F74045F988B4` (`spammer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table special_subject
# ------------------------------------------------------------

DROP TABLE IF EXISTS `special_subject`;

CREATE TABLE `special_subject` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `create_time` datetime NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `banner` varchar(2083) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(1000) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table special_subject_relation
# ------------------------------------------------------------

DROP TABLE IF EXISTS `special_subject_relation`;

CREATE TABLE `special_subject_relation` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `special_subject_id` int(11) DEFAULT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `associated_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `special_subject_index` (`special_subject_id`),
  CONSTRAINT `FK_E1B212EFE65EF837` FOREIGN KEY (`special_subject_id`) REFERENCES `special_subject` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table sticker
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sticker`;

CREATE TABLE `sticker` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `thumbnail_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_new` smallint(6) NOT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `deleted` smallint(6) NOT NULL,
  `last_modified_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table sticker_version
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sticker_version`;

CREATE TABLE `sticker_version` (
  `version` int(11) NOT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table sub_banner
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sub_banner`;

CREATE TABLE `sub_banner` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `target_id` bigint(20) NOT NULL,
  `position` smallint(6) NOT NULL,
  `image_url` varchar(2014) COLLATE utf8_unicode_ci NOT NULL,
  `create_time` datetime NOT NULL,
  `deleted` tinyint(1) NOT NULL,
  `published` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table task_state
# ------------------------------------------------------------

DROP TABLE IF EXISTS `task_state`;

CREATE TABLE `task_state` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `last_check_time` datetime DEFAULT NULL,
  `run_interval` int(11) NOT NULL,
  `next_run_time` datetime DEFAULT NULL,
  `disabled` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_unique` (`task_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table test_topic
# ------------------------------------------------------------

DROP TABLE IF EXISTS `test_topic`;

CREATE TABLE `test_topic` (
  `property` smallint(6) NOT NULL,
  `score` smallint(6) NOT NULL,
  `topic_id` bigint(20) NOT NULL,
  PRIMARY KEY (`property`,`score`,`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table tmp_user_commentcount_0609_0622
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tmp_user_commentcount_0609_0622`;

CREATE TABLE `tmp_user_commentcount_0609_0622` (
  `user_id` bigint(20) NOT NULL,
  `comment_count` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table tmp_user_commentcount_0623
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tmp_user_commentcount_0623`;

CREATE TABLE `tmp_user_commentcount_0623` (
  `user_id` bigint(20) NOT NULL,
  `comment_count` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table tmp_user_has_post_in_kaiche_topic_20180724
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tmp_user_has_post_in_kaiche_topic_20180724`;

CREATE TABLE `tmp_user_has_post_in_kaiche_topic_20180724` (
  `user_id` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table tmp_user_likecount_0609_0622
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tmp_user_likecount_0609_0622`;

CREATE TABLE `tmp_user_likecount_0609_0622` (
  `user_id` bigint(20) NOT NULL,
  `like_count` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table tmp_user_likecount_0623
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tmp_user_likecount_0623`;

CREATE TABLE `tmp_user_likecount_0623` (
  `user_id` bigint(20) NOT NULL,
  `like_count` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table tmp_user_postcount_0609_0622
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tmp_user_postcount_0609_0622`;

CREATE TABLE `tmp_user_postcount_0609_0622` (
  `user_id` bigint(20) NOT NULL,
  `post_count` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table tmp_user_postcount_0623
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tmp_user_postcount_0623`;

CREATE TABLE `tmp_user_postcount_0623` (
  `user_id` bigint(20) NOT NULL,
  `post_count` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table topic
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic`;

CREATE TABLE `topic` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `title` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `summary` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `create_time` datetime NOT NULL,
  `index_image_url` varchar(2083) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cover_image_url` varchar(2083) COLLATE utf8_unicode_ci DEFAULT NULL,
  `post_count` int(11) NOT NULL,
  `creator_id` bigint(20) DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL,
  `manager_id` bigint(20) DEFAULT NULL,
  `op_mark` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `follower_count` int(11) NOT NULL,
  `follower_position` int(11) NOT NULL,
  `apply_to_follow` tinyint(1) NOT NULL DEFAULT '0',
  `private` tinyint(1) NOT NULL DEFAULT '0',
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `color` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `certified` tinyint(1) NOT NULL DEFAULT '0',
  `link_title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `link` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `title_udx` (`title`),
  KEY `creator_idx` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table topic_announcing
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_announcing`;

CREATE TABLE `topic_announcing` (
  `topic_id` bigint(20) NOT NULL,
  `last_announce_time` int(11) NOT NULL,
  `last_announce_post` bigint(20) NOT NULL,
  `last_announce_time2` int(11) DEFAULT NULL,
  `last_announce_post2` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table topic_attr
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_attr`;

CREATE TABLE `topic_attr` (
  `topic_id` bigint(20) NOT NULL,
  `is_kaiche` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否开车次元',
  PRIMARY KEY (`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='次元标签属性';



# Dump of table topic_category
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_category`;

CREATE TABLE `topic_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_F07D94C75E237E06` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table topic_category_rel
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_category_rel`;

CREATE TABLE `topic_category_rel` (
  `category_id` int(11) NOT NULL,
  `topic_id` bigint(20) NOT NULL,
  PRIMARY KEY (`category_id`,`topic_id`),
  KEY `topic_category_idx` (`topic_id`,`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table topic_category_score
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_category_score`;

CREATE TABLE `topic_category_score` (
  `category_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `topic_id` bigint(20) NOT NULL,
  `order` int(11) NOT NULL,
  PRIMARY KEY (`category_id`,`score`,`order`,`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table topic_certified
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_certified`;

CREATE TABLE `topic_certified` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `topic_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table topic_chat_post
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_chat_post`;

CREATE TABLE `topic_chat_post` (
  `topic_id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  PRIMARY KEY (`topic_id`,`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table topic_core_member_meta
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_core_member_meta`;

CREATE TABLE `topic_core_member_meta` (
  `topic_id` bigint(20) NOT NULL,
  `core_member_count` smallint(6) NOT NULL,
  PRIMARY KEY (`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table topic_core_members
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_core_members`;

CREATE TABLE `topic_core_members` (
  `topic_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `order` smallint(6) NOT NULL,
  `title` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`topic_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table topic_creating_application
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_creating_application`;

CREATE TABLE `topic_creating_application` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `apply_time` datetime NOT NULL,
  `creator_id` bigint(20) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(60) COLLATE utf8_unicode_ci NOT NULL,
  `summary` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `index_image_url` varchar(2083) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cover_image_url` varchar(2083) COLLATE utf8_unicode_ci DEFAULT NULL,
  `apply_to_follow` tinyint(1) NOT NULL DEFAULT '0',
  `private` tinyint(1) NOT NULL DEFAULT '0',
  `color` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `title_udx` (`title`),
  KEY `creator_idx` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table topic_creating_quota
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_creating_quota`;

CREATE TABLE `topic_creating_quota` (
  `user_id` bigint(20) NOT NULL,
  `quota` int(11) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table topic_default_group
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_default_group`;

CREATE TABLE `topic_default_group` (
  `topic_id` bigint(20) NOT NULL,
  `group_id` bigint(20) NOT NULL,
  PRIMARY KEY (`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table topic_follower
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_follower`;

CREATE TABLE `topic_follower` (
  `topic_id` bigint(20) NOT NULL,
  `position` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  PRIMARY KEY (`topic_id`,`position`,`user_id`),
  UNIQUE KEY `topic_user` (`topic_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table topic_following_application
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_following_application`;

CREATE TABLE `topic_following_application` (
  `topic_id` bigint(20) NOT NULL,
  `applicant_id` bigint(20) NOT NULL,
  `position` bigint(20) NOT NULL,
  `apply_time` int(11) NOT NULL,
  `apply_description` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`topic_id`,`applicant_id`),
  UNIQUE KEY `topic_position_applicant_udx` (`topic_id`,`position`,`applicant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table topic_group
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_group`;

CREATE TABLE `topic_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `weight` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序权重，值越大，排序越靠前',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_D6F3E8A75E237E06` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table topic_group_rel
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_group_rel`;

CREATE TABLE `topic_group_rel` (
  `topic_id` bigint(20) unsigned NOT NULL COMMENT '次元id，topic.id',
  `group_id` int(10) unsigned NOT NULL COMMENT '次元分组id，topic_group.id',
  `update_time` int(11) NOT NULL,
  PRIMARY KEY (`topic_id`,`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table topic_post
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_post`;

CREATE TABLE `topic_post` (
  `topic_id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  PRIMARY KEY (`topic_id`,`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table topic_sticky_post
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_sticky_post`;

CREATE TABLE `topic_sticky_post` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `topic_id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `create_time` datetime NOT NULL,
  `level` smallint(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `topic_post_index` (`topic_id`,`level`,`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table topic_tag
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_tag`;

CREATE TABLE `topic_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `color` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `order_key` smallint(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_302AC6215E237E06` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table topic_tag_rel
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_tag_rel`;

CREATE TABLE `topic_tag_rel` (
  `topic_id` bigint(20) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  PRIMARY KEY (`topic_id`,`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table topic_titles
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_titles`;

CREATE TABLE `topic_titles` (
  `title` varchar(60) CHARACTER SET utf8mb4 NOT NULL,
  PRIMARY KEY (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table topic_user_following
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_user_following`;

CREATE TABLE `topic_user_following` (
  `user_id` bigint(20) NOT NULL,
  `topic_id` bigint(20) NOT NULL,
  `state` smallint(6) NOT NULL,
  `position` int(11) NOT NULL,
  `create_time` datetime NOT NULL,
  PRIMARY KEY (`user_id`,`topic_id`),
  UNIQUE KEY `user_state_position` (`user_id`,`state`,`position`),
  KEY `time_idx` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table topic_user_meta
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_user_meta`;

CREATE TABLE `topic_user_meta` (
  `user_id` bigint(20) NOT NULL,
  `followee_count` int(11) NOT NULL,
  `followee_position` int(11) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table topic_visitor_counting
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_visitor_counting`;

CREATE TABLE `topic_visitor_counting` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `topic_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `count` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id_topic_id_udx` (`user_id`,`topic_id`),
  KEY `user_id_count_idx` (`user_id`,`count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table topic_visitor_log
# ------------------------------------------------------------

DROP TABLE IF EXISTS `topic_visitor_log`;

CREATE TABLE `topic_visitor_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `topic_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `create_time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table ugsv_bgm
# ------------------------------------------------------------

DROP TABLE IF EXISTS `ugsv_bgm`;

CREATE TABLE `ugsv_bgm` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '歌曲名称',
  `singer_name` varchar(100) NOT NULL COMMENT '歌手名称',
  `size` bigint(20) unsigned NOT NULL COMMENT '文件大小xx字节',
  `duration` int(10) unsigned NOT NULL COMMENT '音频时长xx秒',
  `src` varchar(2083) NOT NULL COMMENT '音频文件完整地址',
  `cover` varchar(2083) NOT NULL COMMENT '封面图完整地址',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  `weight` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序权重，值越大，热门排序越靠前',
  `use_count` bigint(20) unsigned NOT NULL COMMENT '使用次数',
  PRIMARY KEY (`id`),
  KEY `weight` (`weight`),
  KEY `use_count` (`use_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table ugsv_post
# ------------------------------------------------------------

DROP TABLE IF EXISTS `ugsv_post`;

CREATE TABLE `ugsv_post` (
  `post_id` bigint(20) unsigned NOT NULL,
  `sv_id` varchar(255) NOT NULL COMMENT '视频文件id',
  `bgm_id` bigint(20) unsigned NOT NULL COMMENT '背景音乐id',
  `author_id` int(10) unsigned NOT NULL COMMENT '作者用户id',
  `playcount` bigint(20) unsigned NOT NULL COMMENT '播放次数',
  PRIMARY KEY (`post_id`),
  KEY `author_id` (`author_id`),
  KEY `bgm_id` (`bgm_id`),
  KEY `playcount` (`playcount`),
  KEY `sv_id` (`sv_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table ugsv_white_list
# ------------------------------------------------------------

DROP TABLE IF EXISTS `ugsv_white_list`;

CREATE TABLE `ugsv_white_list` (
  `user_id` bigint(20) unsigned NOT NULL COMMENT '用户id',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table user
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user`;

CREATE TABLE `user` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `create_time` datetime NOT NULL,
  `nickname` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signature` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar_url` varchar(2083) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cover_url` varchar(2083) COLLATE utf8_unicode_ci DEFAULT NULL,
  `gender` smallint(6) DEFAULT NULL,
  `area_code` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `frozen` tinyint(1) NOT NULL DEFAULT '0',
  `experience` int(11) NOT NULL DEFAULT '0',
  `level` int(11) NOT NULL DEFAULT '1',
  `ciyo_coin` decimal(20,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `area_code_phone_udx` (`area_code`,`phone`),
  UNIQUE KEY `email_udx` (`email`),
  UNIQUE KEY `nickname_udx` (`nickname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table user_bind_qq_or_phone
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_bind_qq_or_phone`;

CREATE TABLE `user_bind_qq_or_phone` (
  `user_id` bigint(20) NOT NULL,
  `phone` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `qq_open_id` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='绑定了手机号或者qq号的用户';



# Dump of table user_blacklist
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_blacklist`;

CREATE TABLE `user_blacklist` (
  `user_id` bigint(20) NOT NULL,
  `target_id` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`,`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table user_comment
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_comment`;

CREATE TABLE `user_comment` (
  `user_id` bigint(20) NOT NULL,
  `comment_id` bigint(20) NOT NULL,
  `has_image` tinyint(1) NOT NULL,
  PRIMARY KEY (`user_id`,`comment_id`),
  KEY `use_image_comment_idx` (`user_id`,`has_image`,`comment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table user_comment_image
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_comment_image`;

CREATE TABLE `user_comment_image` (
  `user_id` bigint(20) NOT NULL,
  `comment_id` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`,`comment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table user_counting
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_counting`;

CREATE TABLE `user_counting` (
  `user_id` bigint(20) NOT NULL,
  `post_count` int(11) NOT NULL,
  `image_comment_count` int(11) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table user_device
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_device`;

CREATE TABLE `user_device` (
  `user_id` bigint(20) NOT NULL,
  `platform` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `device_id` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table user_following
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_following`;

CREATE TABLE `user_following` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `follower_id` bigint(20) NOT NULL,
  `followee_id` bigint(20) NOT NULL,
  `state` smallint(6) NOT NULL,
  `update_time` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `follower_followee_state_udx` (`follower_id`,`followee_id`,`state`),
  UNIQUE KEY `follower_state_id_udx` (`follower_id`,`state`,`id`),
  KEY `update_time_idx` (`update_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table user_following_counting
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_following_counting`;

CREATE TABLE `user_following_counting` (
  `target_id` bigint(20) NOT NULL,
  `follower_count` int(11) DEFAULT NULL,
  `followee_count` int(11) DEFAULT NULL,
  PRIMARY KEY (`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table user_frozen
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_frozen`;

CREATE TABLE `user_frozen` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `time` datetime NOT NULL,
  `reason` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table user_live
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_live`;

CREATE TABLE `user_live` (
  `user_id` bigint(20) NOT NULL,
  `pizus_id` varchar(100) NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `pizus_udx` (`pizus_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table user_mission_state
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_mission_state`;

CREATE TABLE `user_mission_state` (
  `user_id` bigint(20) NOT NULL,
  `filled_profile` smallint(6) NOT NULL,
  `invited_friends` smallint(6) NOT NULL,
  `followed_topic` smallint(6) NOT NULL,
  `daily_date` date DEFAULT NULL,
  `daily_like_post` smallint(6) NOT NULL,
  `daily_comment` smallint(6) NOT NULL,
  `daily_img_comment` smallint(6) NOT NULL,
  `daily_share` smallint(6) NOT NULL,
  `daily_post` smallint(6) NOT NULL,
  `daily_signin` smallint(6) NOT NULL,
  `activated` tinyint(1) NOT NULL,
  `set_favorite_topic` smallint(6) NOT NULL,
  `set_attributes` smallint(6) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table user_post
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_post`;

CREATE TABLE `user_post` (
  `user_id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `topic_id` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`,`post_id`),
  UNIQUE KEY `user_topic_post_udx` (`user_id`,`topic_id`,`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table user_profile
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_profile`;

CREATE TABLE `user_profile` (
  `user_id` bigint(20) NOT NULL,
  `cover_url` varchar(2083) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signature` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `honmei` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attributes` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `skills` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `constellation` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `location` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `community` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fancy` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `blood_type` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table user_sign_in
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_sign_in`;

CREATE TABLE `user_sign_in` (
  `user_id` bigint(20) NOT NULL,
  `time` datetime NOT NULL,
  `os` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `os_version` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `device_id` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `device_type` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `client_version` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table user_topic_creating
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_topic_creating`;

CREATE TABLE `user_topic_creating` (
  `user_id` bigint(20) NOT NULL,
  `topic_id` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`,`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table user_topic_managing
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_topic_managing`;

CREATE TABLE `user_topic_managing` (
  `user_id` bigint(20) NOT NULL,
  `topic_id` bigint(20) NOT NULL,
  PRIMARY KEY (`user_id`,`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;



# Dump of table user_vip
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_vip`;

CREATE TABLE `user_vip` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `certification_text` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_udx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table voting
# ------------------------------------------------------------

DROP TABLE IF EXISTS `voting`;

CREATE TABLE `voting` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) NOT NULL,
  `title` varchar(60) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(400) COLLATE utf8_unicode_ci DEFAULT NULL,
  `vote_count` int(11) NOT NULL DEFAULT '0',
  `opt1` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `opt1_count` int(11) NOT NULL DEFAULT '0',
  `opt2` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `opt2_count` int(11) NOT NULL DEFAULT '0',
  `opt3` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `opt3_count` int(11) DEFAULT '0',
  `opt4` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `opt4_count` int(11) DEFAULT '0',
  `opt5` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `opt5_count` int(11) DEFAULT '0',
  `opt6` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `opt6_count` int(11) DEFAULT '0',
  `opt7` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `opt7_count` int(11) DEFAULT '0',
  `opt8` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `opt8_count` int(11) DEFAULT '0',
  `opt9` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `opt9_count` int(11) DEFAULT '0',
  `opt10` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `opt10_count` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table voting_voters
# ------------------------------------------------------------

DROP TABLE IF EXISTS `voting_voters`;

CREATE TABLE `voting_voters` (
  `voting_id` bigint(20) NOT NULL,
  `voter_id` bigint(20) NOT NULL,
  `option` smallint(6) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`voting_id`,`voter_id`),
  KEY `voting_option_time_voter_idx` (`voting_id`,`option`,`time`,`voter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
