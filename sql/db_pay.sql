-- MySQL dump 10.13  Distrib 5.7.12, for Linux (x86_64)
--
-- Host: localhost    Database: db_pay
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
-- Table structure for table `apple_orders_ban`
--

DROP TABLE IF EXISTS `apple_orders_ban`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `apple_orders_ban` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `orderid` varchar(50) NOT NULL DEFAULT '' COMMENT '我方订单号',
  `trade_orderid` varchar(50) NOT NULL DEFAULT '' COMMENT '第三方交易平台订单号',
  `user_name` varchar(50) NOT NULL DEFAULT '',
  `user_ip` int(11) unsigned NOT NULL COMMENT 'ip',
  `game_id` int(11) unsigned NOT NULL,
  `server_id` int(11) NOT NULL,
  `money` int(11) unsigned NOT NULL DEFAULT '0',
  `pay_time` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `apple_orders_ban`
--

LOCK TABLES `apple_orders_ban` WRITE;
/*!40000 ALTER TABLE `apple_orders_ban` DISABLE KEYS */;
/*!40000 ALTER TABLE `apple_orders_ban` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `follow_up_link`
--

DROP TABLE IF EXISTS `follow_up_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `follow_up_link` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_names` varchar(50) NOT NULL DEFAULT '' COMMENT '玩家账号',
  `follow_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '跟进人员uid',
  `follow_username` varchar(50) NOT NULL DEFAULT '',
  `follow_realname` varchar(20) NOT NULL DEFAULT '' COMMENT '跟进人员真实姓名',
  `follow_datetime` datetime NOT NULL COMMENT ' 跟进时间',
  `return_visit_times` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '回访次数',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `follow_up_link`
--

LOCK TABLES `follow_up_link` WRITE;
/*!40000 ALTER TABLE `follow_up_link` DISABLE KEYS */;
/*!40000 ALTER TABLE `follow_up_link` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `follow_up_pay`
--

DROP TABLE IF EXISTS `follow_up_pay`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `follow_up_pay` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '玩家回访记录关联表ID',
  `follow_user` varchar(20) NOT NULL DEFAULT '' COMMENT '跟进人',
  `follow_realname` varchar(20) NOT NULL DEFAULT '' COMMENT '跟进人真实姓名',
  `follow_time` datetime NOT NULL COMMENT '跟进时间',
  `return_visit_time` datetime NOT NULL COMMENT '回访时间',
  `return_visit_remark` varchar(512) NOT NULL DEFAULT '' COMMENT '回访结果备注',
  PRIMARY KEY (`id`),
  KEY `visit_time` (`return_visit_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `follow_up_pay`
--

LOCK TABLES `follow_up_pay` WRITE;
/*!40000 ALTER TABLE `follow_up_pay` DISABLE KEYS */;
/*!40000 ALTER TABLE `follow_up_pay` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `game_list`
--

DROP TABLE IF EXISTS `game_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `game_byname` varchar(30) NOT NULL,
  `b_name` varchar(50) NOT NULL COMMENT '游戏币名称',
  `first_letter` varchar(2) NOT NULL,
  `exchange_rate` int(11) NOT NULL,
  `is_open` tinyint(4) NOT NULL,
  `rank` smallint(6) NOT NULL,
  `owner` tinyint(4) NOT NULL DEFAULT '0',
  `back_result` varchar(10) DEFAULT NULL,
  `pay_url` varchar(200) DEFAULT NULL,
  `fcmathod` tinyint(4) NOT NULL DEFAULT '0',
  `fcvalue` varchar(60) NOT NULL DEFAULT '0',
  `weight` tinyint(4) NOT NULL DEFAULT '0' COMMENT '推荐',
  `app_name` varchar(256) DEFAULT NULL COMMENT '应用名称:全民烧猪',
  `app_id` varchar(128) DEFAULT NULL COMMENT '应用标识com.tanwan.qmsz',
  `release_state` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0是沙箱，1是正式环境',
  `game_url` varchar(600) DEFAULT '' COMMENT '游戏URL',
  `download_url` varchar(255) DEFAULT '' COMMENT '游戏下载地址',
  `icon` varchar(255) DEFAULT '' COMMENT '游戏图标',
  `type_name` varchar(64) NOT NULL DEFAULT '' COMMENT '游戏类型',
  `star` int(2) DEFAULT '5' COMMENT '等级',
  `os` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1PC,2IOS,3Android,4H5',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `game_list`
--

LOCK TABLES `game_list` WRITE;
/*!40000 ALTER TABLE `game_list` DISABLE KEYS */;
INSERT INTO `game_list` VALUES (1,'安卓测试Demo','ALLAND','元宝','A',10,0,500,0,'1','',0,'0',0,'安卓测试Demo','com.android.demo',0,'','','','',5,3);
/*!40000 ALTER TABLE `game_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `game_orders`
--

DROP TABLE IF EXISTS `game_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_orders` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `orderid` varchar(50) NOT NULL DEFAULT '' COMMENT '我司订单号',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0,已提交1,已更新,2已补发',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`orderid`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `game_orders`
--

LOCK TABLES `game_orders` WRITE;
/*!40000 ALTER TABLE `game_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `game_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `game_server_list`
--

DROP TABLE IF EXISTS `game_server_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_server_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `game_id` int(11) NOT NULL,
  `pay_url` varchar(200) NOT NULL,
  `is_open` tinyint(4) NOT NULL,
  `create_date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `game_server_list`
--

LOCK TABLES `game_server_list` WRITE;
/*!40000 ALTER TABLE `game_server_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `game_server_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_ALLAND_log`
--

DROP TABLE IF EXISTS `pay_ALLAND_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_ALLAND_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderid` varchar(50) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT '',
  `payname` varchar(50) NOT NULL DEFAULT '',
  `user_name` varchar(50) NOT NULL,
  `passtype` varchar(50) NOT NULL DEFAULT '',
  `money` varchar(50) NOT NULL,
  `paid_amount` float DEFAULT NULL,
  `pay_type` int(11) DEFAULT NULL,
  `pay_gold` int(11) DEFAULT NULL,
  `code` varchar(50) NOT NULL DEFAULT '',
  `sign` varchar(200) NOT NULL DEFAULT '',
  `pay_date` datetime NOT NULL,
  `user_ip` varchar(50) NOT NULL,
  `back_result` varchar(200) NOT NULL DEFAULT '',
  `pay_result` varchar(200) NOT NULL DEFAULT '',
  `game_id` int(10) unsigned DEFAULT NULL COMMENT '游戏id',
  `server_id` int(11) NOT NULL,
  `remark` varchar(200) NOT NULL,
  `stat` tinyint(4) NOT NULL DEFAULT '0',
  `pay_url` varchar(500) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `orderid` (`orderid`),
  KEY `user_name` (`user_name`),
  KEY `pay_date` (`pay_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_ALLAND_log`
--

LOCK TABLES `pay_ALLAND_log` WRITE;
/*!40000 ALTER TABLE `pay_ALLAND_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_ALLAND_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_ALLH5_log`
--

DROP TABLE IF EXISTS `pay_ALLH5_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_ALLH5_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderid` varchar(50) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT '',
  `payname` varchar(50) NOT NULL DEFAULT '',
  `user_name` varchar(50) NOT NULL,
  `passtype` varchar(50) NOT NULL DEFAULT '',
  `money` varchar(50) NOT NULL,
  `paid_amount` float DEFAULT NULL,
  `pay_type` int(11) DEFAULT NULL,
  `pay_gold` int(11) DEFAULT NULL,
  `code` varchar(50) NOT NULL DEFAULT '',
  `sign` varchar(200) NOT NULL DEFAULT '',
  `pay_date` datetime DEFAULT NULL,
  `user_ip` varchar(50) NOT NULL,
  `back_result` varchar(200) NOT NULL DEFAULT '',
  `pay_result` varchar(200) NOT NULL DEFAULT '',
  `game_id` int(10) unsigned NOT NULL,
  `server_id` int(11) NOT NULL,
  `remark` varchar(200) NOT NULL,
  `stat` tinyint(4) NOT NULL DEFAULT '0',
  `pay_url` varchar(512) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `orderid` (`orderid`),
  KEY `user_name` (`user_name`),
  KEY `pay_date` (`pay_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_ALLH5_log`
--

LOCK TABLES `pay_ALLH5_log` WRITE;
/*!40000 ALTER TABLE `pay_ALLH5_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_ALLH5_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_ALLIOS_log`
--

DROP TABLE IF EXISTS `pay_ALLIOS_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_ALLIOS_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderid` varchar(50) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT '',
  `payname` varchar(50) NOT NULL DEFAULT '',
  `user_name` varchar(50) NOT NULL,
  `passtype` varchar(50) NOT NULL DEFAULT '',
  `money` varchar(50) NOT NULL,
  `paid_amount` float DEFAULT NULL,
  `pay_type` int(11) DEFAULT NULL,
  `pay_gold` int(11) DEFAULT NULL,
  `code` varchar(50) NOT NULL DEFAULT '',
  `sign` varchar(200) NOT NULL DEFAULT '',
  `pay_date` datetime NOT NULL,
  `user_ip` varchar(50) NOT NULL,
  `back_result` varchar(200) NOT NULL DEFAULT '',
  `pay_result` varchar(200) NOT NULL DEFAULT '',
  `game_id` int(10) unsigned DEFAULT NULL COMMENT '游戏id',
  `server_id` int(11) NOT NULL,
  `remark` varchar(200) NOT NULL,
  `stat` tinyint(4) NOT NULL DEFAULT '0',
  `pay_url` varchar(500) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `orderid` (`orderid`),
  KEY `user_name` (`user_name`),
  KEY `pay_date` (`pay_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_ALLIOS_log`
--

LOCK TABLES `pay_ALLIOS_log` WRITE;
/*!40000 ALTER TABLE `pay_ALLIOS_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_ALLIOS_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_apple_log`
--

DROP TABLE IF EXISTS `pay_apple_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_apple_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bid` varchar(50) NOT NULL,
  `bvrs` varchar(10) NOT NULL,
  `item_id` varchar(32) NOT NULL,
  `o_p_date` bigint(20) NOT NULL,
  `purchase_date` bigint(20) NOT NULL,
  `product_id` varchar(64) NOT NULL,
  `o_transaction_id` varchar(32) NOT NULL,
  `transaction_id` varchar(32) NOT NULL,
  `idfa` varchar(64) NOT NULL,
  `game_id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `user_name` varchar(30) NOT NULL,
  `addtime` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_apple_log`
--

LOCK TABLES `pay_apple_log` WRITE;
/*!40000 ALTER TABLE `pay_apple_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_apple_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_channel`
--

DROP TABLE IF EXISTS `pay_channel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_channel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pay_way_id` int(11) NOT NULL,
  `rate` float NOT NULL COMMENT '支付费率',
  `remark` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_channel`
--

LOCK TABLES `pay_channel` WRITE;
/*!40000 ALTER TABLE `pay_channel` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_channel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_list`
--

DROP TABLE IF EXISTS `pay_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderid` varchar(50) NOT NULL,
  `trade_orderid` varchar(50) NOT NULL,
  `user_name` varchar(50) NOT NULL,
  `pay_way_id` tinyint(4) NOT NULL,
  `money` float NOT NULL,
  `paid_amount` float unsigned NOT NULL,
  `pay_date` datetime NOT NULL,
  `agent_id` int(11) NOT NULL,
  `placeid` varchar(100) DEFAULT '0',
  `cplaceid` varchar(50) DEFAULT NULL,
  `adid` varchar(100) DEFAULT NULL,
  `game_id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `reg_date` datetime NOT NULL,
  `from_url` varchar(250) NOT NULL,
  `cid` int(11) NOT NULL,
  `sync_date` datetime NOT NULL,
  `bank_type` tinyint(4) NOT NULL DEFAULT '1',
  `user_ip` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orderid` (`orderid`),
  KEY `agent_id` (`agent_id`),
  KEY `pay_date` (`pay_date`),
  KEY `game_id` (`game_id`),
  KEY `server_id` (`server_id`),
  KEY `user_name` (`user_name`),
  KEY `sync_date` (`sync_date`),
  KEY `trade_orderid` (`trade_orderid`),
  KEY `reg_date` (`reg_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_list`
--

LOCK TABLES `pay_list` WRITE;
/*!40000 ALTER TABLE `pay_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_orders`
--

DROP TABLE IF EXISTS `pay_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_orders` (
  `orderid` varchar(50) NOT NULL COMMENT '我司订单号',
  `trade_orderid` varchar(50) DEFAULT '' COMMENT '第三方交易平台订单号',
  `user_name` varchar(50) NOT NULL,
  `role_id` varchar(50) NOT NULL DEFAULT '0' COMMENT '角色ID',
  `role_name` varchar(50) NOT NULL COMMENT '角色名',
  `money` int(11) NOT NULL,
  `paid_amount` float unsigned NOT NULL DEFAULT '0',
  `pay_gold` int(11) NOT NULL DEFAULT '0',
  `game_gold` int(11) DEFAULT '0' COMMENT '游戏币',
  `game_id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `pay_channel` tinyint(3) unsigned NOT NULL,
  `user_ip` bigint(20) DEFAULT '0',
  `pay_date` int(11) NOT NULL,
  `sync_date` int(11) NOT NULL DEFAULT '0',
  `succ` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`orderid`),
  KEY `user_name` (`user_name`),
  KEY `trade_orderid` (`trade_orderid`),
  KEY `sync_date` (`sync_date`),
  KEY `idx_pay_date` (`pay_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_orders`
--

LOCK TABLES `pay_orders` WRITE;
/*!40000 ALTER TABLE `pay_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sdk_pay_orders`
--

DROP TABLE IF EXISTS `sdk_pay_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sdk_pay_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderid` varchar(60) NOT NULL COMMENT '我方订单号',
  `tradeid` varchar(60) DEFAULT '' COMMENT '交易流水号',
  `pay_channel` smallint(6) NOT NULL,
  `money` int(11) NOT NULL COMMENT '面额(元)',
  `paid_amount` float unsigned NOT NULL COMMENT '净额(元)',
  `b_num` int(10) NOT NULL DEFAULT '0',
  `b_flag` tinyint(4) NOT NULL DEFAULT '0' COMMENT '异步通知:0未,1已',
  `user_ip` varchar(15) NOT NULL COMMENT '用户IP',
  `user_name` varchar(30) NOT NULL,
  `game_id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `payname` varchar(50) NOT NULL,
  `pay_date` datetime NOT NULL COMMENT '支付时间',
  `sync_date` datetime DEFAULT NULL COMMENT '支付通知时间',
  `pay_result` varchar(20) DEFAULT '' COMMENT '第三方支付返回值',
  `succ` tinyint(2) NOT NULL DEFAULT '0' COMMENT '1已支付,0未支付',
  `agent_id` int(11) NOT NULL DEFAULT '0',
  `site_id` int(11) NOT NULL DEFAULT '0',
  `other_data` varchar(1000) DEFAULT '' COMMENT '其他数据',
  `ext` varchar(200) DEFAULT NULL COMMENT '扩展参数',
  `mtype` int(11) NOT NULL DEFAULT '1' COMMENT '第三方标识2:msdk,3:360',
  `openid` varchar(60) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `orderid` (`orderid`),
  KEY `user_name` (`user_name`),
  KEY `tradeid` (`tradeid`),
  KEY `game_id` (`game_id`),
  KEY `ext` (`ext`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='sdk订单';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sdk_pay_orders`
--

LOCK TABLES `sdk_pay_orders` WRITE;
/*!40000 ALTER TABLE `sdk_pay_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `sdk_pay_orders` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-10-30 15:48:03
