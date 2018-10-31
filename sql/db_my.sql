-- MySQL dump 10.13  Distrib 5.7.12, for Linux (x86_64)
--
-- Host: localhost    Database: db_my
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
-- Table structure for table `help_appeal`
--

DROP TABLE IF EXISTS `help_appeal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `help_appeal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loginname` varchar(20) DEFAULT NULL COMMENT '当前登录',
  `username` varchar(20) DEFAULT NULL COMMENT '问题帐号',
  `role_name` varchar(50) NOT NULL,
  `plat_id` tinyint(4) NOT NULL DEFAULT '1',
  `game_id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `server_name` varchar(30) NOT NULL COMMENT '区服名',
  `truename` varchar(20) DEFAULT NULL COMMENT '真实姓名',
  `idcard` varchar(20) DEFAULT NULL COMMENT '身份证号码',
  `reg_date` date DEFAULT NULL COMMENT '注册日期',
  `reg_city` varchar(10) DEFAULT NULL COMMENT '注册城市',
  `often_login_city` varchar(50) DEFAULT NULL COMMENT '常登陆地点',
  `last_login_city` varchar(20) NOT NULL COMMENT '最后登录地点',
  `play_game` varchar(50) DEFAULT NULL COMMENT '曾经登录的游戏',
  `bind_email` varchar(30) NOT NULL COMMENT '绑定的邮箱',
  `bind_phone` varchar(15) NOT NULL COMMENT '绑定的手机',
  `pay_log` varchar(500) NOT NULL COMMENT '充值记录',
  `device_id` varchar(60) NOT NULL COMMENT '手机设备号',
  `lose_date` datetime NOT NULL,
  `lose_info` varchar(500) NOT NULL,
  `reset_value` varchar(20) NOT NULL COMMENT '重置值',
  `apply_email` varchar(30) NOT NULL COMMENT '联系邮箱',
  `apply_time` datetime NOT NULL COMMENT '申诉时间',
  `ip` varchar(15) NOT NULL,
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1重置密码,2重置邮箱,3重置手机,4物品被盗,5二级密码,6背包密码,7仓库密码',
  `state` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:未回复,2:正在处理,3:等待玩家回复,4:已处理-通过,5:已处理-不通过',
  `answer` varchar(200) NOT NULL,
  `atime` datetime NOT NULL COMMENT '回复时间',
  `code` varchar(500) NOT NULL COMMENT '查询验证码',
  PRIMARY KEY (`id`),
  KEY `code` (`code`(255)),
  KEY `username` (`username`),
  KEY `apply_time` (`apply_time`),
  KEY `game_id` (`game_id`),
  KEY `plat_id` (`plat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='重置账号信息申诉';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `help_appeal`
--

LOCK TABLES `help_appeal` WRITE;
/*!40000 ALTER TABLE `help_appeal` DISABLE KEYS */;
/*!40000 ALTER TABLE `help_appeal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `help_question`
--

DROP TABLE IF EXISTS `help_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `help_question` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `qid` int(11) NOT NULL DEFAULT '0' COMMENT '所属问题ID',
  `loginname` varchar(20) DEFAULT NULL COMMENT '登录帐号',
  `username` varchar(20) DEFAULT NULL COMMENT '问题帐号',
  `role_name` varchar(20) DEFAULT NULL COMMENT '问题角色',
  `game_id` int(11) DEFAULT NULL COMMENT '游戏id',
  `server_id` mediumint(9) DEFAULT NULL COMMENT '游戏服id',
  `truename` varchar(14) NOT NULL,
  `idcard` varchar(20) NOT NULL,
  `title` varchar(40) NOT NULL COMMENT '问题标题',
  `content` text COMMENT '问题内容',
  `content2` text NOT NULL COMMENT '外挂描述',
  `email` varchar(50) DEFAULT NULL COMMENT '联系邮箱',
  `telephone` varchar(15) DEFAULT NULL COMMENT '联系电话',
  `qq` varchar(15) NOT NULL,
  `pics` tinytext COMMENT '问题截图',
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1bug建议,2投诉,3表扬,4外挂,5充值',
  `state` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:未回复,2:正在处理,3:等待玩家回复,4:已处理',
  `qtime` datetime NOT NULL COMMENT '提交时间',
  `ip` varchar(32) DEFAULT NULL,
  `isRead` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否阅读,1未读',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `help_question`
--

LOCK TABLES `help_question` WRITE;
/*!40000 ALTER TABLE `help_question` DISABLE KEYS */;
/*!40000 ALTER TABLE `help_question` ENABLE KEYS */;
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
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-10-30 15:48:15
