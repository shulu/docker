-- MySQL dump 10.13  Distrib 5.7.12, for Linux (x86_64)
--
-- Host: localhost    Database: db_www
-- ------------------------------------------------------
-- Server version	5.7.12-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `app_code_config`
--

DROP TABLE IF EXISTS `app_code_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_code_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hd` varchar(20) NOT NULL,
  `plat_id` tinyint(3) unsigned NOT NULL DEFAULT '2',
  `game_id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `num` int(10) unsigned NOT NULL,
  `s_date` date NOT NULL,
  `e_date` date NOT NULL,
  `content` varchar(255) NOT NULL,
  `usage` varchar(255) NOT NULL COMMENT '用法',
  `img` varchar(255) NOT NULL,
  `isapp` tinyint(3) unsigned NOT NULL,
  `code_table` varchar(30) NOT NULL COMMENT '礼包表',
  `code_type` tinyint(3) unsigned NOT NULL,
  `code_url` varchar(255) NOT NULL,
  `top` int(10) unsigned NOT NULL,
  `state` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `hd` (`hd`),
  KEY `game_id` (`game_id`),
  KEY `num` (`num`),
  KEY `end_date` (`e_date`),
  KEY `isapp` (`isapp`),
  KEY `code_type` (`code_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='礼包配置';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_code_config`
--

LOCK TABLES `app_code_config` WRITE;
/*!40000 ALTER TABLE `app_code_config` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_code_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_code_user`
--

DROP TABLE IF EXISTS `app_code_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_code_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(30) NOT NULL,
  `hd` varchar(20) NOT NULL,
  `code` varchar(60) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_name` (`user_name`,`hd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='APP用户礼包';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_code_user`
--

LOCK TABLES `app_code_user` WRITE;
/*!40000 ALTER TABLE `app_code_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_code_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_download_total`
--

DROP TABLE IF EXISTS `app_download_total`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_download_total` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `os` tinyint(4) NOT NULL,
  `tdate` date NOT NULL,
  `total` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `game_id` (`game_id`),
  KEY `os` (`os`),
  KEY `tdate` (`tdate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='app下载统计';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_download_total`
--

LOCK TABLES `app_download_total` WRITE;
/*!40000 ALTER TABLE `app_download_total` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_download_total` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_games_config`
--

DROP TABLE IF EXISTS `app_games_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_games_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(10) unsigned NOT NULL,
  `img` varchar(250) NOT NULL,
  `top` int(10) unsigned NOT NULL,
  `state` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '1显示',
  PRIMARY KEY (`id`),
  UNIQUE KEY `game_id` (`game_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='游戏列表显示设置';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_games_config`
--

LOCK TABLES `app_games_config` WRITE;
/*!40000 ALTER TABLE `app_games_config` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_games_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_news_config`
--

DROP TABLE IF EXISTS `app_news_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_news_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(10) unsigned NOT NULL,
  `show_num` tinyint(3) unsigned NOT NULL COMMENT '每天显示数',
  `state` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '1显示',
  PRIMARY KEY (`id`),
  UNIQUE KEY `game_id` (`game_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='公告弹窗设置';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_news_config`
--

LOCK TABLES `app_news_config` WRITE;
/*!40000 ALTER TABLE `app_news_config` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_news_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_notice`
--

DROP TABLE IF EXISTS `app_notice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_notice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` varchar(255) NOT NULL,
  `author` varchar(30) NOT NULL,
  `create_time` int(11) unsigned NOT NULL,
  `start_time` int(11) unsigned NOT NULL,
  `top` int(10) unsigned NOT NULL,
  `state` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `create_time` (`create_time`),
  KEY `author` (`author`),
  KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='通知消息';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_notice`
--

LOCK TABLES `app_notice` WRITE;
/*!40000 ALTER TABLE `app_notice` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_notice` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `app_notice_read`
--

DROP TABLE IF EXISTS `app_notice_read`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_notice_read` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notice_id` int(10) unsigned NOT NULL,
  `user_name` varchar(30) NOT NULL,
  `create_time` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_name` (`user_name`),
  KEY `notice_id` (`notice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='通知消息-阅读';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_notice_read`
--

LOCK TABLES `app_notice_read` WRITE;
/*!40000 ALTER TABLE `app_notice_read` DISABLE KEYS */;
/*!40000 ALTER TABLE `app_notice_read` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forgame_ad`
--

DROP TABLE IF EXISTS `forgame_ad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forgame_ad` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `platform` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '平台0:所有1:IOS2:Android',
  `type_id` int(10) unsigned NOT NULL COMMENT '所属广告类型',
  `ad_position_id` int(10) unsigned NOT NULL COMMENT '所属广告位',
  `ad_name` varchar(50) NOT NULL COMMENT '广告名称',
  `ad_img_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '广告图片id',
  `img_url` varchar(500) DEFAULT '' COMMENT '广告位图片',
  `statistics_url` varchar(500) DEFAULT '' COMMENT '数据统计地址',
  `relev_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联id',
  `order_id` int(10) unsigned DEFAULT '0' COMMENT '排序',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '0:停用,1:正常,2:删除',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='广告数据表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forgame_ad`
--

LOCK TABLES `forgame_ad` WRITE;
/*!40000 ALTER TABLE `forgame_ad` DISABLE KEYS */;
/*!40000 ALTER TABLE `forgame_ad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forgame_ad_position`
--

DROP TABLE IF EXISTS `forgame_ad_position`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forgame_ad_position` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `ad_position_name` varchar(50) DEFAULT NULL COMMENT '广告位名称',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='广告位数据表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forgame_ad_position`
--

LOCK TABLES `forgame_ad_position` WRITE;
/*!40000 ALTER TABLE `forgame_ad_position` DISABLE KEYS */;
/*!40000 ALTER TABLE `forgame_ad_position` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forgame_giftbag`
--

DROP TABLE IF EXISTS `forgame_giftbag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forgame_giftbag` (
  `giftbag_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '游戏礼包ID',
  `giftbag_name` varchar(64) NOT NULL DEFAULT '' COMMENT '游戏礼包名称',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '游戏礼包描述',
  `details` varchar(255) DEFAULT '' COMMENT '游戏礼包内容',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '礼包ICON',
  `game_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '游戏ID',
  `server_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '服务器ID',
  `remain` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '游戏礼包码剩余数',
  `total` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '游戏礼包码总数',
  `startdate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '有效时间',
  `enddate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '有效时间',
  `status` int(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态 （0关闭，1开启）',
  `mapping_id` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`giftbag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forgame_giftbag`
--

LOCK TABLES `forgame_giftbag` WRITE;
/*!40000 ALTER TABLE `forgame_giftbag` DISABLE KEYS */;
/*!40000 ALTER TABLE `forgame_giftbag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forgame_giftbag_code`
--

DROP TABLE IF EXISTS `forgame_giftbag_code`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forgame_giftbag_code` (
  `giftbag_code_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '礼包码ID',
  `giftbag_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '所属游戏礼包',
  `giftbag_code` varchar(64) NOT NULL DEFAULT '' COMMENT '游戏礼包码',
  `state` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '0可领取，1已领取，2不可用，3通用码',
  PRIMARY KEY (`giftbag_code_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forgame_giftbag_code`
--

LOCK TABLES `forgame_giftbag_code` WRITE;
/*!40000 ALTER TABLE `forgame_giftbag_code` DISABLE KEYS */;
/*!40000 ALTER TABLE `forgame_giftbag_code` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forgame_user_giftbag`
--

DROP TABLE IF EXISTS `forgame_user_giftbag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forgame_user_giftbag` (
  `user_giftbag_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `giftbag_id` int(11) unsigned NOT NULL DEFAULT '0',
  `giftbag_code` varchar(64) NOT NULL,
  `gtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '领取时间',
  PRIMARY KEY (`user_giftbag_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forgame_user_giftbag`
--

LOCK TABLES `forgame_user_giftbag` WRITE;
/*!40000 ALTER TABLE `forgame_user_giftbag` DISABLE KEYS */;
/*!40000 ALTER TABLE `forgame_user_giftbag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `game_login_info`
--

DROP TABLE IF EXISTS `game_login_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_login_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户平台ID',
  `user_name` varchar(64) NOT NULL DEFAULT '' COMMENT '用户平台名',
  `game_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '游戏ID',
  `addtime` int(11) DEFAULT '0' COMMENT '登陆时间',
  `updatetime` int(11) NOT NULL DEFAULT '0',
  `login_info` varchar(255) NOT NULL DEFAULT '' COMMENT '其他信息',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`,`user_name`,`game_id`),
  KEY `idx_uid` (`uid`),
  KEY `idx_uname` (`user_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `game_login_info`
--

LOCK TABLES `game_login_info` WRITE;
/*!40000 ALTER TABLE `game_login_info` DISABLE KEYS */;
/*!40000 ALTER TABLE `game_login_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `msdk_fields`
--

DROP TABLE IF EXISTS `msdk_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `msdk_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uname` varchar(20) NOT NULL,
  `game_id` int(11) NOT NULL,
  `mtype` int(11) NOT NULL,
  `other_data` varchar(1000) NOT NULL,
  `ltime` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uname` (`uname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `msdk_fields`
--

LOCK TABLES `msdk_fields` WRITE;
/*!40000 ALTER TABLE `msdk_fields` DISABLE KEYS */;
/*!40000 ALTER TABLE `msdk_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `msg_mo_logs`
--

DROP TABLE IF EXISTS `msg_mo_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `msg_mo_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `msgid` int(11) NOT NULL,
  `mobile` varchar(13) NOT NULL,
  `content` varchar(20) NOT NULL,
  `addtime` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `msg_mo_logs`
--

LOCK TABLES `msg_mo_logs` WRITE;
/*!40000 ALTER TABLE `msg_mo_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `msg_mo_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `opgroup_ad_notice`
--

DROP TABLE IF EXISTS `opgroup_ad_notice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `opgroup_ad_notice` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(64) NOT NULL DEFAULT '',
  `content` text,
  `game_id` tinyint(1) unsigned DEFAULT '0',
  `validate_start` int(11) unsigned NOT NULL DEFAULT '0',
  `validate_end` int(11) unsigned NOT NULL DEFAULT '0',
  `popup_type` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0普通公告 1广告 2紧急通知',
  `notice_tag` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1,一般 2,重要',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `opgroup_ad_notice`
--

LOCK TABLES `opgroup_ad_notice` WRITE;
/*!40000 ALTER TABLE `opgroup_ad_notice` DISABLE KEYS */;
/*!40000 ALTER TABLE `opgroup_ad_notice` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_feedback`
--

DROP TABLE IF EXISTS `user_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '用户平台ID',
  `user_name` varchar(64) NOT NULL DEFAULT '' COMMENT '平台用户名',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '反馈描述',
  `contact` varchar(64) NOT NULL DEFAULT '' COMMENT '联系方式',
  `os` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '来源平台 （4,H5 web）',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_feedback`
--

LOCK TABLES `user_feedback` WRITE;
/*!40000 ALTER TABLE `user_feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_imei`
--

DROP TABLE IF EXISTS `user_imei`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_imei` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `openid` varchar(60) DEFAULT NULL,
  `game_id` int(11) NOT NULL DEFAULT '0',
  `uid` bigint(20) NOT NULL DEFAULT '0',
  `user_name` varchar(20) NOT NULL,
  `agent_id` int(11) NOT NULL DEFAULT '1000',
  `site_id` int(11) NOT NULL DEFAULT '1000',
  `imei` varchar(60) NOT NULL,
  `ip` bigint(20) DEFAULT NULL,
  `mtype` tinyint(4) NOT NULL DEFAULT '1',
  `cplaceid` varchar(50) DEFAULT '',
  `reg_time` int(12) NOT NULL,
  `model` varchar(32) NOT NULL DEFAULT '' COMMENT '设备机型',
  PRIMARY KEY (`id`),
  UNIQUE KEY `openid` (`openid`,`user_name`,`mtype`),
  KEY `imei` (`imei`),
  KEY `user_name` (`user_name`),
  KEY `reg_time` (`reg_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_imei`
--

LOCK TABLES `user_imei` WRITE;
/*!40000 ALTER TABLE `user_imei` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_imei` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_login_ban`
--

DROP TABLE IF EXISTS `user_login_ban`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_login_ban` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `mid` int(11) unsigned NOT NULL,
  `imei` varchar(60) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_login_ban`
--

LOCK TABLES `user_login_ban` WRITE;
/*!40000 ALTER TABLE `user_login_ban` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_login_ban` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_logs`
--

DROP TABLE IF EXISTS `user_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `log_ip` varchar(32) NOT NULL,
  `log_time` datetime NOT NULL,
  `log_type` tinyint(4) NOT NULL COMMENT '0:登陆1:完善资料2:修改密码3:找回密码4:防沉迷5:帐号锁定6:密保问题7:封停账号8:手机绑定9:邮箱绑定,10手机解绑,11邮箱解绑,12二级密码,13第三方账号绑定,14解封账号',
  `log_state` tinyint(4) NOT NULL COMMENT '0:操作失败1:操作成功2:等待响应中',
  `memo` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  KEY `log_type` (`log_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_logs`
--

LOCK TABLES `user_logs` WRITE;
/*!40000 ALTER TABLE `user_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_msg_send_log`
--

DROP TABLE IF EXISTS `user_msg_send_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_msg_send_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mobile` varchar(15) NOT NULL,
  `send_time` int(11) NOT NULL,
  `msg_result` tinyint(4) NOT NULL COMMENT '发送结果,1成功',
  `ip` bigint(20) NOT NULL,
  `qf` tinyint(4) NOT NULL COMMENT '0验证码,1普通群发,2个性群发',
  `qf_num` int(11) NOT NULL DEFAULT '1' COMMENT '发送数量',
  `message` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `mobile` (`mobile`),
  KEY `ip` (`ip`),
  KEY `send_time` (`send_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='短信发送日志';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_msg_send_log`
--

LOCK TABLES `user_msg_send_log` WRITE;
/*!40000 ALTER TABLE `user_msg_send_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_msg_send_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wx_user`
--

DROP TABLE IF EXISTS `wx_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wx_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wx_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '微信公众号平台ID',
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户平台ID',
  `openid` varchar(64) NOT NULL DEFAULT '' COMMENT '微信openid',
  `user_name` varchar(64) NOT NULL DEFAULT '' COMMENT '用户平台名',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wx_user`
--

LOCK TABLES `wx_user` WRITE;
/*!40000 ALTER TABLE `wx_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `wx_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wx_user_link`
--

DROP TABLE IF EXISTS `wx_user_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wx_user_link` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username_new` varchar(20) NOT NULL DEFAULT '',
  `username_old` varchar(20) NOT NULL DEFAULT '',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wx_user_link`
--

LOCK TABLES `wx_user_link` WRITE;
/*!40000 ALTER TABLE `wx_user_link` DISABLE KEYS */;
/*!40000 ALTER TABLE `wx_user_link` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-10-30 15:47:41
