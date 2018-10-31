-- MySQL dump 10.13  Distrib 5.7.12, for Linux (x86_64)
--
-- Host: localhost    Database: db_users
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
-- Table structure for table `user_0`
--

DROP TABLE IF EXISTS `user_0`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_0` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL COMMENT '用户UID',
  `bbs_uid` int(11) DEFAULT '0' COMMENT '论坛用户id',
  `user_name` varchar(50) NOT NULL COMMENT '用户名',
  `user_pwd` varchar(35) NOT NULL COMMENT '用户密码',
  `email` varchar(50) NOT NULL COMMENT '用户邮箱',
  `integral` int(11) NOT NULL DEFAULT '0' COMMENT '用户积分',
  `nick_name` varchar(50) DEFAULT NULL COMMENT '用户昵称',
  `true_name` varchar(50) DEFAULT NULL COMMENT '用户真实名',
  `sex` varchar(4) NOT NULL DEFAULT '男' COMMENT '性别',
  `id_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '证件类型',
  `id_card` varchar(20) DEFAULT NULL COMMENT '证件号码',
  `birthday` varchar(10) DEFAULT NULL COMMENT '生日',
  `telephone` varchar(20) DEFAULT NULL COMMENT '电话',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机',
  `address` varchar(200) DEFAULT NULL COMMENT '地址',
  `zipcode` int(6) DEFAULT NULL COMMENT '邮编',
  `level` tinyint(3) NOT NULL DEFAULT '0' COMMENT '等级',
  `qq` varchar(50) DEFAULT NULL,
  `msn` varchar(30) DEFAULT NULL,
  `question` varchar(200) DEFAULT NULL COMMENT '密保问题',
  `answer` varchar(200) DEFAULT NULL COMMENT '密保答案',
  `head_pic` varchar(200) NOT NULL DEFAULT '/media/no_head_pic.gif' COMMENT '个人头像',
  `defendboss` varchar(200) DEFAULT NULL COMMENT '防老板键',
  `reg_time` int(11) NOT NULL COMMENT '注册时间',
  `reg_ip` bigint(20) DEFAULT NULL COMMENT '注册ip',
  `login_ip` varchar(100) DEFAULT NULL COMMENT '登录IP',
  `login_time` int(11) NOT NULL COMMENT '登录时间',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '注册类型(1微信, 2游客账号)，是否已设置密码',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_0`
--

LOCK TABLES `user_0` WRITE;
/*!40000 ALTER TABLE `user_0` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_0` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_1`
--

DROP TABLE IF EXISTS `user_1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_1` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL COMMENT '用户UID',
  `bbs_uid` int(11) DEFAULT '0' COMMENT '论坛用户id',
  `user_name` varchar(50) NOT NULL COMMENT '用户名',
  `user_pwd` varchar(35) NOT NULL COMMENT '用户密码',
  `email` varchar(50) NOT NULL COMMENT '用户邮箱',
  `integral` int(11) NOT NULL DEFAULT '0' COMMENT '用户积分',
  `nick_name` varchar(50) DEFAULT NULL COMMENT '用户昵称',
  `true_name` varchar(50) DEFAULT NULL COMMENT '用户真实名',
  `sex` varchar(4) NOT NULL DEFAULT '男' COMMENT '性别',
  `id_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '证件类型',
  `id_card` varchar(20) DEFAULT NULL COMMENT '证件号码',
  `birthday` varchar(10) DEFAULT NULL COMMENT '生日',
  `telephone` varchar(20) DEFAULT NULL COMMENT '电话',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机',
  `address` varchar(200) DEFAULT NULL COMMENT '地址',
  `zipcode` int(6) DEFAULT NULL COMMENT '邮编',
  `level` tinyint(3) NOT NULL DEFAULT '0' COMMENT '等级',
  `qq` varchar(50) DEFAULT NULL,
  `msn` varchar(30) DEFAULT NULL,
  `question` varchar(200) DEFAULT NULL COMMENT '密保问题',
  `answer` varchar(200) DEFAULT NULL COMMENT '密保答案',
  `head_pic` varchar(200) NOT NULL DEFAULT '/media/no_head_pic.gif' COMMENT '个人头像',
  `defendboss` varchar(200) DEFAULT NULL COMMENT '防老板键',
  `reg_time` int(11) NOT NULL COMMENT '注册时间',
  `reg_ip` bigint(20) DEFAULT NULL COMMENT '注册ip',
  `login_ip` varchar(100) DEFAULT NULL COMMENT '登录IP',
  `login_time` int(11) NOT NULL COMMENT '登录时间',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '注册类型(1微信, 2游客账号)，是否已设置密码',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_1`
--

LOCK TABLES `user_1` WRITE;
/*!40000 ALTER TABLE `user_1` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_1` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_10`
--

DROP TABLE IF EXISTS `user_10`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_10` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL COMMENT '用户UID',
  `bbs_uid` int(11) DEFAULT '0' COMMENT '论坛用户id',
  `user_name` varchar(50) NOT NULL COMMENT '用户名',
  `user_pwd` varchar(35) NOT NULL COMMENT '用户密码',
  `email` varchar(50) NOT NULL COMMENT '用户邮箱',
  `integral` int(11) NOT NULL DEFAULT '0' COMMENT '用户积分',
  `nick_name` varchar(50) DEFAULT NULL COMMENT '用户昵称',
  `true_name` varchar(50) DEFAULT NULL COMMENT '用户真实名',
  `sex` varchar(4) NOT NULL DEFAULT '男' COMMENT '性别',
  `id_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '证件类型',
  `id_card` varchar(20) DEFAULT NULL COMMENT '证件号码',
  `birthday` varchar(10) DEFAULT NULL COMMENT '生日',
  `telephone` varchar(20) DEFAULT NULL COMMENT '电话',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机',
  `address` varchar(200) DEFAULT NULL COMMENT '地址',
  `zipcode` int(6) DEFAULT NULL COMMENT '邮编',
  `level` tinyint(3) NOT NULL DEFAULT '0' COMMENT '等级',
  `qq` varchar(50) DEFAULT NULL,
  `msn` varchar(30) DEFAULT NULL,
  `question` varchar(200) DEFAULT NULL COMMENT '密保问题',
  `answer` varchar(200) DEFAULT NULL COMMENT '密保答案',
  `head_pic` varchar(200) NOT NULL DEFAULT '/media/no_head_pic.gif' COMMENT '个人头像',
  `defendboss` varchar(200) DEFAULT NULL COMMENT '防老板键',
  `reg_time` int(11) NOT NULL COMMENT '注册时间',
  `reg_ip` bigint(20) DEFAULT NULL COMMENT '注册ip',
  `login_ip` varchar(100) DEFAULT NULL COMMENT '登录IP',
  `login_time` int(11) NOT NULL COMMENT '登录时间',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '注册类型(1微信, 2游客账号)，是否已设置密码',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_10`
--

LOCK TABLES `user_10` WRITE;
/*!40000 ALTER TABLE `user_10` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_10` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_11`
--

DROP TABLE IF EXISTS `user_11`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_11` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL COMMENT '用户UID',
  `bbs_uid` int(11) DEFAULT '0' COMMENT '论坛用户id',
  `user_name` varchar(50) NOT NULL COMMENT '用户名',
  `user_pwd` varchar(35) NOT NULL COMMENT '用户密码',
  `email` varchar(50) NOT NULL COMMENT '用户邮箱',
  `integral` int(11) NOT NULL DEFAULT '0' COMMENT '用户积分',
  `nick_name` varchar(50) DEFAULT NULL COMMENT '用户昵称',
  `true_name` varchar(50) DEFAULT NULL COMMENT '用户真实名',
  `sex` varchar(4) NOT NULL DEFAULT '男' COMMENT '性别',
  `id_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '证件类型',
  `id_card` varchar(20) DEFAULT NULL COMMENT '证件号码',
  `birthday` varchar(10) DEFAULT NULL COMMENT '生日',
  `telephone` varchar(20) DEFAULT NULL COMMENT '电话',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机',
  `address` varchar(200) DEFAULT NULL COMMENT '地址',
  `zipcode` int(6) DEFAULT NULL COMMENT '邮编',
  `level` tinyint(3) NOT NULL DEFAULT '0' COMMENT '等级',
  `qq` varchar(50) DEFAULT NULL,
  `msn` varchar(30) DEFAULT NULL,
  `question` varchar(200) DEFAULT NULL COMMENT '密保问题',
  `answer` varchar(200) DEFAULT NULL COMMENT '密保答案',
  `head_pic` varchar(200) NOT NULL DEFAULT '/media/no_head_pic.gif' COMMENT '个人头像',
  `defendboss` varchar(200) DEFAULT NULL COMMENT '防老板键',
  `reg_time` int(11) NOT NULL COMMENT '注册时间',
  `reg_ip` bigint(20) DEFAULT NULL COMMENT '注册ip',
  `login_ip` varchar(100) DEFAULT NULL COMMENT '登录IP',
  `login_time` int(11) NOT NULL COMMENT '登录时间',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '注册类型(1微信, 2游客账号)，是否已设置密码',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_11`
--

LOCK TABLES `user_11` WRITE;
/*!40000 ALTER TABLE `user_11` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_11` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_12`
--

DROP TABLE IF EXISTS `user_12`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_12` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL COMMENT '用户UID',
  `bbs_uid` int(11) DEFAULT '0' COMMENT '论坛用户id',
  `user_name` varchar(50) NOT NULL COMMENT '用户名',
  `user_pwd` varchar(35) NOT NULL COMMENT '用户密码',
  `email` varchar(50) NOT NULL COMMENT '用户邮箱',
  `integral` int(11) NOT NULL DEFAULT '0' COMMENT '用户积分',
  `nick_name` varchar(50) DEFAULT NULL COMMENT '用户昵称',
  `true_name` varchar(50) DEFAULT NULL COMMENT '用户真实名',
  `sex` varchar(4) NOT NULL DEFAULT '男' COMMENT '性别',
  `id_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '证件类型',
  `id_card` varchar(20) DEFAULT NULL COMMENT '证件号码',
  `birthday` varchar(10) DEFAULT NULL COMMENT '生日',
  `telephone` varchar(20) DEFAULT NULL COMMENT '电话',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机',
  `address` varchar(200) DEFAULT NULL COMMENT '地址',
  `zipcode` int(6) DEFAULT NULL COMMENT '邮编',
  `level` tinyint(3) NOT NULL DEFAULT '0' COMMENT '等级',
  `qq` varchar(50) DEFAULT NULL,
  `msn` varchar(30) DEFAULT NULL,
  `question` varchar(200) DEFAULT NULL COMMENT '密保问题',
  `answer` varchar(200) DEFAULT NULL COMMENT '密保答案',
  `head_pic` varchar(200) NOT NULL DEFAULT '/media/no_head_pic.gif' COMMENT '个人头像',
  `defendboss` varchar(200) DEFAULT NULL COMMENT '防老板键',
  `reg_time` int(11) NOT NULL COMMENT '注册时间',
  `reg_ip` bigint(20) DEFAULT NULL COMMENT '注册ip',
  `login_ip` varchar(100) DEFAULT NULL COMMENT '登录IP',
  `login_time` int(11) NOT NULL COMMENT '登录时间',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '注册类型(1微信, 2游客账号)，是否已设置密码',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_12`
--

LOCK TABLES `user_12` WRITE;
/*!40000 ALTER TABLE `user_12` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_12` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_13`
--

DROP TABLE IF EXISTS `user_13`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_13` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL COMMENT '用户UID',
  `bbs_uid` int(11) DEFAULT '0' COMMENT '论坛用户id',
  `user_name` varchar(50) NOT NULL COMMENT '用户名',
  `user_pwd` varchar(35) NOT NULL COMMENT '用户密码',
  `email` varchar(50) NOT NULL COMMENT '用户邮箱',
  `integral` int(11) NOT NULL DEFAULT '0' COMMENT '用户积分',
  `nick_name` varchar(50) DEFAULT NULL COMMENT '用户昵称',
  `true_name` varchar(50) DEFAULT NULL COMMENT '用户真实名',
  `sex` varchar(4) NOT NULL DEFAULT '男' COMMENT '性别',
  `id_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '证件类型',
  `id_card` varchar(20) DEFAULT NULL COMMENT '证件号码',
  `birthday` varchar(10) DEFAULT NULL COMMENT '生日',
  `telephone` varchar(20) DEFAULT NULL COMMENT '电话',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机',
  `address` varchar(200) DEFAULT NULL COMMENT '地址',
  `zipcode` int(6) DEFAULT NULL COMMENT '邮编',
  `level` tinyint(3) NOT NULL DEFAULT '0' COMMENT '等级',
  `qq` varchar(50) DEFAULT NULL,
  `msn` varchar(30) DEFAULT NULL,
  `question` varchar(200) DEFAULT NULL COMMENT '密保问题',
  `answer` varchar(200) DEFAULT NULL COMMENT '密保答案',
  `head_pic` varchar(200) NOT NULL DEFAULT '/media/no_head_pic.gif' COMMENT '个人头像',
  `defendboss` varchar(200) DEFAULT NULL COMMENT '防老板键',
  `reg_time` int(11) NOT NULL COMMENT '注册时间',
  `reg_ip` bigint(20) DEFAULT NULL COMMENT '注册ip',
  `login_ip` varchar(100) DEFAULT NULL COMMENT '登录IP',
  `login_time` int(11) NOT NULL COMMENT '登录时间',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '注册类型(1微信, 2游客账号)，是否已设置密码',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_13`
--

LOCK TABLES `user_13` WRITE;
/*!40000 ALTER TABLE `user_13` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_13` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_14`
--

DROP TABLE IF EXISTS `user_14`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_14` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL COMMENT '用户UID',
  `bbs_uid` int(11) DEFAULT '0' COMMENT '论坛用户id',
  `user_name` varchar(50) NOT NULL COMMENT '用户名',
  `user_pwd` varchar(35) NOT NULL COMMENT '用户密码',
  `email` varchar(50) NOT NULL COMMENT '用户邮箱',
  `integral` int(11) NOT NULL DEFAULT '0' COMMENT '用户积分',
  `nick_name` varchar(50) DEFAULT NULL COMMENT '用户昵称',
  `true_name` varchar(50) DEFAULT NULL COMMENT '用户真实名',
  `sex` varchar(4) NOT NULL DEFAULT '男' COMMENT '性别',
  `id_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '证件类型',
  `id_card` varchar(20) DEFAULT NULL COMMENT '证件号码',
  `birthday` varchar(10) DEFAULT NULL COMMENT '生日',
  `telephone` varchar(20) DEFAULT NULL COMMENT '电话',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机',
  `address` varchar(200) DEFAULT NULL COMMENT '地址',
  `zipcode` int(6) DEFAULT NULL COMMENT '邮编',
  `level` tinyint(3) NOT NULL DEFAULT '0' COMMENT '等级',
  `qq` varchar(50) DEFAULT NULL,
  `msn` varchar(30) DEFAULT NULL,
  `question` varchar(200) DEFAULT NULL COMMENT '密保问题',
  `answer` varchar(200) DEFAULT NULL COMMENT '密保答案',
  `head_pic` varchar(200) NOT NULL DEFAULT '/media/no_head_pic.gif' COMMENT '个人头像',
  `defendboss` varchar(200) DEFAULT NULL COMMENT '防老板键',
  `reg_time` int(11) NOT NULL COMMENT '注册时间',
  `reg_ip` bigint(20) DEFAULT NULL COMMENT '注册ip',
  `login_ip` varchar(100) DEFAULT NULL COMMENT '登录IP',
  `login_time` int(11) NOT NULL COMMENT '登录时间',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '注册类型(1微信, 2游客账号)，是否已设置密码',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_14`
--

LOCK TABLES `user_14` WRITE;
/*!40000 ALTER TABLE `user_14` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_14` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_15`
--

DROP TABLE IF EXISTS `user_15`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_15` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL COMMENT '用户UID',
  `bbs_uid` int(11) DEFAULT '0' COMMENT '论坛用户id',
  `user_name` varchar(50) NOT NULL COMMENT '用户名',
  `user_pwd` varchar(35) NOT NULL COMMENT '用户密码',
  `email` varchar(50) NOT NULL COMMENT '用户邮箱',
  `integral` int(11) NOT NULL DEFAULT '0' COMMENT '用户积分',
  `nick_name` varchar(50) DEFAULT NULL COMMENT '用户昵称',
  `true_name` varchar(50) DEFAULT NULL COMMENT '用户真实名',
  `sex` varchar(4) NOT NULL DEFAULT '男' COMMENT '性别',
  `id_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '证件类型',
  `id_card` varchar(20) DEFAULT NULL COMMENT '证件号码',
  `birthday` varchar(10) DEFAULT NULL COMMENT '生日',
  `telephone` varchar(20) DEFAULT NULL COMMENT '电话',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机',
  `address` varchar(200) DEFAULT NULL COMMENT '地址',
  `zipcode` int(6) DEFAULT NULL COMMENT '邮编',
  `level` tinyint(3) NOT NULL DEFAULT '0' COMMENT '等级',
  `qq` varchar(50) DEFAULT NULL,
  `msn` varchar(30) DEFAULT NULL,
  `question` varchar(200) DEFAULT NULL COMMENT '密保问题',
  `answer` varchar(200) DEFAULT NULL COMMENT '密保答案',
  `head_pic` varchar(200) NOT NULL DEFAULT '/media/no_head_pic.gif' COMMENT '个人头像',
  `defendboss` varchar(200) DEFAULT NULL COMMENT '防老板键',
  `reg_time` int(11) NOT NULL COMMENT '注册时间',
  `reg_ip` bigint(20) DEFAULT NULL COMMENT '注册ip',
  `login_ip` varchar(100) DEFAULT NULL COMMENT '登录IP',
  `login_time` int(11) NOT NULL COMMENT '登录时间',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '注册类型(1微信, 2游客账号)，是否已设置密码',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_15`
--

LOCK TABLES `user_15` WRITE;
/*!40000 ALTER TABLE `user_15` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_15` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_16`
--

DROP TABLE IF EXISTS `user_16`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_16` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL COMMENT '用户UID',
  `bbs_uid` int(11) DEFAULT '0' COMMENT '论坛用户id',
  `user_name` varchar(50) NOT NULL COMMENT '用户名',
  `user_pwd` varchar(35) NOT NULL COMMENT '用户密码',
  `email` varchar(50) NOT NULL COMMENT '用户邮箱',
  `integral` int(11) NOT NULL DEFAULT '0' COMMENT '用户积分',
  `nick_name` varchar(50) DEFAULT NULL COMMENT '用户昵称',
  `true_name` varchar(50) DEFAULT NULL COMMENT '用户真实名',
  `sex` varchar(4) NOT NULL DEFAULT '男' COMMENT '性别',
  `id_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '证件类型',
  `id_card` varchar(20) DEFAULT NULL COMMENT '证件号码',
  `birthday` varchar(10) DEFAULT NULL COMMENT '生日',
  `telephone` varchar(20) DEFAULT NULL COMMENT '电话',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机',
  `address` varchar(200) DEFAULT NULL COMMENT '地址',
  `zipcode` int(6) DEFAULT NULL COMMENT '邮编',
  `level` tinyint(3) NOT NULL DEFAULT '0' COMMENT '等级',
  `qq` varchar(50) DEFAULT NULL,
  `msn` varchar(30) DEFAULT NULL,
  `question` varchar(200) DEFAULT NULL COMMENT '密保问题',
  `answer` varchar(200) DEFAULT NULL COMMENT '密保答案',
  `head_pic` varchar(200) NOT NULL DEFAULT '/media/no_head_pic.gif' COMMENT '个人头像',
  `defendboss` varchar(200) DEFAULT NULL COMMENT '防老板键',
  `reg_time` int(11) NOT NULL COMMENT '注册时间',
  `reg_ip` bigint(20) DEFAULT NULL COMMENT '注册ip',
  `login_ip` varchar(100) DEFAULT NULL COMMENT '登录IP',
  `login_time` int(11) NOT NULL COMMENT '登录时间',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '注册类型(1微信, 2游客账号)，是否已设置密码',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_16`
--

LOCK TABLES `user_16` WRITE;
/*!40000 ALTER TABLE `user_16` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_16` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_17`
--

DROP TABLE IF EXISTS `user_17`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_17` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL COMMENT '用户UID',
  `bbs_uid` int(11) DEFAULT '0' COMMENT '论坛用户id',
  `user_name` varchar(50) NOT NULL COMMENT '用户名',
  `user_pwd` varchar(35) NOT NULL COMMENT '用户密码',
  `email` varchar(50) NOT NULL COMMENT '用户邮箱',
  `integral` int(11) NOT NULL DEFAULT '0' COMMENT '用户积分',
  `nick_name` varchar(50) DEFAULT NULL COMMENT '用户昵称',
  `true_name` varchar(50) DEFAULT NULL COMMENT '用户真实名',
  `sex` varchar(4) NOT NULL DEFAULT '男' COMMENT '性别',
  `id_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '证件类型',
  `id_card` varchar(20) DEFAULT NULL COMMENT '证件号码',
  `birthday` varchar(10) DEFAULT NULL COMMENT '生日',
  `telephone` varchar(20) DEFAULT NULL COMMENT '电话',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机',
  `address` varchar(200) DEFAULT NULL COMMENT '地址',
  `zipcode` int(6) DEFAULT NULL COMMENT '邮编',
  `level` tinyint(3) NOT NULL DEFAULT '0' COMMENT '等级',
  `qq` varchar(50) DEFAULT NULL,
  `msn` varchar(30) DEFAULT NULL,
  `question` varchar(200) DEFAULT NULL COMMENT '密保问题',
  `answer` varchar(200) DEFAULT NULL COMMENT '密保答案',
  `head_pic` varchar(200) NOT NULL DEFAULT '/media/no_head_pic.gif' COMMENT '个人头像',
  `defendboss` varchar(200) DEFAULT NULL COMMENT '防老板键',
  `reg_time` int(11) NOT NULL COMMENT '注册时间',
  `reg_ip` bigint(20) DEFAULT NULL COMMENT '注册ip',
  `login_ip` varchar(100) DEFAULT NULL COMMENT '登录IP',
  `login_time` int(11) NOT NULL COMMENT '登录时间',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '注册类型(1微信, 2游客账号)，是否已设置密码',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_17`
--

LOCK TABLES `user_17` WRITE;
/*!40000 ALTER TABLE `user_17` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_17` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_18`
--

DROP TABLE IF EXISTS `user_18`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_18` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL COMMENT '用户UID',
  `bbs_uid` int(11) DEFAULT '0' COMMENT '论坛用户id',
  `user_name` varchar(50) NOT NULL COMMENT '用户名',
  `user_pwd` varchar(35) NOT NULL COMMENT '用户密码',
  `email` varchar(50) NOT NULL COMMENT '用户邮箱',
  `integral` int(11) NOT NULL DEFAULT '0' COMMENT '用户积分',
  `nick_name` varchar(50) DEFAULT NULL COMMENT '用户昵称',
  `true_name` varchar(50) DEFAULT NULL COMMENT '用户真实名',
  `sex` varchar(4) NOT NULL DEFAULT '男' COMMENT '性别',
  `id_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '证件类型',
  `id_card` varchar(20) DEFAULT NULL COMMENT '证件号码',
  `birthday` varchar(10) DEFAULT NULL COMMENT '生日',
  `telephone` varchar(20) DEFAULT NULL COMMENT '电话',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机',
  `address` varchar(200) DEFAULT NULL COMMENT '地址',
  `zipcode` int(6) DEFAULT NULL COMMENT '邮编',
  `level` tinyint(3) NOT NULL DEFAULT '0' COMMENT '等级',
  `qq` varchar(50) DEFAULT NULL,
  `msn` varchar(30) DEFAULT NULL,
  `question` varchar(200) DEFAULT NULL COMMENT '密保问题',
  `answer` varchar(200) DEFAULT NULL COMMENT '密保答案',
  `head_pic` varchar(200) NOT NULL DEFAULT '/media/no_head_pic.gif' COMMENT '个人头像',
  `defendboss` varchar(200) DEFAULT NULL COMMENT '防老板键',
  `reg_time` int(11) NOT NULL COMMENT '注册时间',
  `reg_ip` bigint(20) DEFAULT NULL COMMENT '注册ip',
  `login_ip` varchar(100) DEFAULT NULL COMMENT '登录IP',
  `login_time` int(11) NOT NULL COMMENT '登录时间',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '注册类型(1微信, 2游客账号)，是否已设置密码',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_18`
--

LOCK TABLES `user_18` WRITE;
/*!40000 ALTER TABLE `user_18` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_18` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_19`
--

DROP TABLE IF EXISTS `user_19`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_19` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL COMMENT '用户UID',
  `bbs_uid` int(11) DEFAULT '0' COMMENT '论坛用户id',
  `user_name` varchar(50) NOT NULL COMMENT '用户名',
  `user_pwd` varchar(35) NOT NULL COMMENT '用户密码',
  `email` varchar(50) NOT NULL COMMENT '用户邮箱',
  `integral` int(11) NOT NULL DEFAULT '0' COMMENT '用户积分',
  `nick_name` varchar(50) DEFAULT NULL COMMENT '用户昵称',
  `true_name` varchar(50) DEFAULT NULL COMMENT '用户真实名',
  `sex` varchar(4) NOT NULL DEFAULT '男' COMMENT '性别',
  `id_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '证件类型',
  `id_card` varchar(20) DEFAULT NULL COMMENT '证件号码',
  `birthday` varchar(10) DEFAULT NULL COMMENT '生日',
  `telephone` varchar(20) DEFAULT NULL COMMENT '电话',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机',
  `address` varchar(200) DEFAULT NULL COMMENT '地址',
  `zipcode` int(6) DEFAULT NULL COMMENT '邮编',
  `level` tinyint(3) NOT NULL DEFAULT '0' COMMENT '等级',
  `qq` varchar(50) DEFAULT NULL,
  `msn` varchar(30) DEFAULT NULL,
  `question` varchar(200) DEFAULT NULL COMMENT '密保问题',
  `answer` varchar(200) DEFAULT NULL COMMENT '密保答案',
  `head_pic` varchar(200) NOT NULL DEFAULT '/media/no_head_pic.gif' COMMENT '个人头像',
  `defendboss` varchar(200) DEFAULT NULL COMMENT '防老板键',
  `reg_time` int(11) NOT NULL COMMENT '注册时间',
  `reg_ip` bigint(20) DEFAULT NULL COMMENT '注册ip',
  `login_ip` varchar(100) DEFAULT NULL COMMENT '登录IP',
  `login_time` int(11) NOT NULL COMMENT '登录时间',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '注册类型(1微信, 2游客账号)，是否已设置密码',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_19`
--

LOCK TABLES `user_19` WRITE;
/*!40000 ALTER TABLE `user_19` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_19` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_2`
--

DROP TABLE IF EXISTS `user_2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL COMMENT '用户UID',
  `bbs_uid` int(11) DEFAULT '0' COMMENT '论坛用户id',
  `user_name` varchar(50) NOT NULL COMMENT '用户名',
  `user_pwd` varchar(35) NOT NULL COMMENT '用户密码',
  `email` varchar(50) NOT NULL COMMENT '用户邮箱',
  `integral` int(11) NOT NULL DEFAULT '0' COMMENT '用户积分',
  `nick_name` varchar(50) DEFAULT NULL COMMENT '用户昵称',
  `true_name` varchar(50) DEFAULT NULL COMMENT '用户真实名',
  `sex` varchar(4) NOT NULL DEFAULT '男' COMMENT '性别',
  `id_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '证件类型',
  `id_card` varchar(20) DEFAULT NULL COMMENT '证件号码',
  `birthday` varchar(10) DEFAULT NULL COMMENT '生日',
  `telephone` varchar(20) DEFAULT NULL COMMENT '电话',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机',
  `address` varchar(200) DEFAULT NULL COMMENT '地址',
  `zipcode` int(6) DEFAULT NULL COMMENT '邮编',
  `level` tinyint(3) NOT NULL DEFAULT '0' COMMENT '等级',
  `qq` varchar(50) DEFAULT NULL,
  `msn` varchar(30) DEFAULT NULL,
  `question` varchar(200) DEFAULT NULL COMMENT '密保问题',
  `answer` varchar(200) DEFAULT NULL COMMENT '密保答案',
  `head_pic` varchar(200) NOT NULL DEFAULT '/media/no_head_pic.gif' COMMENT '个人头像',
  `defendboss` varchar(200) DEFAULT NULL COMMENT '防老板键',
  `reg_time` int(11) NOT NULL COMMENT '注册时间',
  `reg_ip` bigint(20) DEFAULT NULL COMMENT '注册ip',
  `login_ip` varchar(100) DEFAULT NULL COMMENT '登录IP',
  `login_time` int(11) NOT NULL COMMENT '登录时间',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '注册类型(1微信, 2游客账号)，是否已设置密码',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_2`
--

LOCK TABLES `user_2` WRITE;
/*!40000 ALTER TABLE `user_2` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_2` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_3`
--

DROP TABLE IF EXISTS `user_3`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_3` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL COMMENT '用户UID',
  `bbs_uid` int(11) DEFAULT '0' COMMENT '论坛用户id',
  `user_name` varchar(50) NOT NULL COMMENT '用户名',
  `user_pwd` varchar(35) NOT NULL COMMENT '用户密码',
  `email` varchar(50) NOT NULL COMMENT '用户邮箱',
  `integral` int(11) NOT NULL DEFAULT '0' COMMENT '用户积分',
  `nick_name` varchar(50) DEFAULT NULL COMMENT '用户昵称',
  `true_name` varchar(50) DEFAULT NULL COMMENT '用户真实名',
  `sex` varchar(4) NOT NULL DEFAULT '男' COMMENT '性别',
  `id_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '证件类型',
  `id_card` varchar(20) DEFAULT NULL COMMENT '证件号码',
  `birthday` varchar(10) DEFAULT NULL COMMENT '生日',
  `telephone` varchar(20) DEFAULT NULL COMMENT '电话',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机',
  `address` varchar(200) DEFAULT NULL COMMENT '地址',
  `zipcode` int(6) DEFAULT NULL COMMENT '邮编',
  `level` tinyint(3) NOT NULL DEFAULT '0' COMMENT '等级',
  `qq` varchar(50) DEFAULT NULL,
  `msn` varchar(30) DEFAULT NULL,
  `question` varchar(200) DEFAULT NULL COMMENT '密保问题',
  `answer` varchar(200) DEFAULT NULL COMMENT '密保答案',
  `head_pic` varchar(200) NOT NULL DEFAULT '/media/no_head_pic.gif' COMMENT '个人头像',
  `defendboss` varchar(200) DEFAULT NULL COMMENT '防老板键',
  `reg_time` int(11) NOT NULL COMMENT '注册时间',
  `reg_ip` bigint(20) DEFAULT NULL COMMENT '注册ip',
  `login_ip` varchar(100) DEFAULT NULL COMMENT '登录IP',
  `login_time` int(11) NOT NULL COMMENT '登录时间',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '注册类型(1微信, 2游客账号)，是否已设置密码',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_3`
--

LOCK TABLES `user_3` WRITE;
/*!40000 ALTER TABLE `user_3` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_3` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_4`
--

DROP TABLE IF EXISTS `user_4`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_4` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL COMMENT '用户UID',
  `bbs_uid` int(11) DEFAULT '0' COMMENT '论坛用户id',
  `user_name` varchar(50) NOT NULL COMMENT '用户名',
  `user_pwd` varchar(35) NOT NULL COMMENT '用户密码',
  `email` varchar(50) NOT NULL COMMENT '用户邮箱',
  `integral` int(11) NOT NULL DEFAULT '0' COMMENT '用户积分',
  `nick_name` varchar(50) DEFAULT NULL COMMENT '用户昵称',
  `true_name` varchar(50) DEFAULT NULL COMMENT '用户真实名',
  `sex` varchar(4) NOT NULL DEFAULT '男' COMMENT '性别',
  `id_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '证件类型',
  `id_card` varchar(20) DEFAULT NULL COMMENT '证件号码',
  `birthday` varchar(10) DEFAULT NULL COMMENT '生日',
  `telephone` varchar(20) DEFAULT NULL COMMENT '电话',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机',
  `address` varchar(200) DEFAULT NULL COMMENT '地址',
  `zipcode` int(6) DEFAULT NULL COMMENT '邮编',
  `level` tinyint(3) NOT NULL DEFAULT '0' COMMENT '等级',
  `qq` varchar(50) DEFAULT NULL,
  `msn` varchar(30) DEFAULT NULL,
  `question` varchar(200) DEFAULT NULL COMMENT '密保问题',
  `answer` varchar(200) DEFAULT NULL COMMENT '密保答案',
  `head_pic` varchar(200) NOT NULL DEFAULT '/media/no_head_pic.gif' COMMENT '个人头像',
  `defendboss` varchar(200) DEFAULT NULL COMMENT '防老板键',
  `reg_time` int(11) NOT NULL COMMENT '注册时间',
  `reg_ip` bigint(20) DEFAULT NULL COMMENT '注册ip',
  `login_ip` varchar(100) DEFAULT NULL COMMENT '登录IP',
  `login_time` int(11) NOT NULL COMMENT '登录时间',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '注册类型(1微信, 2游客账号)，是否已设置密码',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_4`
--

LOCK TABLES `user_4` WRITE;
/*!40000 ALTER TABLE `user_4` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_4` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_5`
--

DROP TABLE IF EXISTS `user_5`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_5` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL COMMENT '用户UID',
  `bbs_uid` int(11) DEFAULT '0' COMMENT '论坛用户id',
  `user_name` varchar(50) NOT NULL COMMENT '用户名',
  `user_pwd` varchar(35) NOT NULL COMMENT '用户密码',
  `email` varchar(50) NOT NULL COMMENT '用户邮箱',
  `integral` int(11) NOT NULL DEFAULT '0' COMMENT '用户积分',
  `nick_name` varchar(50) DEFAULT NULL COMMENT '用户昵称',
  `true_name` varchar(50) DEFAULT NULL COMMENT '用户真实名',
  `sex` varchar(4) NOT NULL DEFAULT '男' COMMENT '性别',
  `id_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '证件类型',
  `id_card` varchar(20) DEFAULT NULL COMMENT '证件号码',
  `birthday` varchar(10) DEFAULT NULL COMMENT '生日',
  `telephone` varchar(20) DEFAULT NULL COMMENT '电话',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机',
  `address` varchar(200) DEFAULT NULL COMMENT '地址',
  `zipcode` int(6) DEFAULT NULL COMMENT '邮编',
  `level` tinyint(3) NOT NULL DEFAULT '0' COMMENT '等级',
  `qq` varchar(50) DEFAULT NULL,
  `msn` varchar(30) DEFAULT NULL,
  `question` varchar(200) DEFAULT NULL COMMENT '密保问题',
  `answer` varchar(200) DEFAULT NULL COMMENT '密保答案',
  `head_pic` varchar(200) NOT NULL DEFAULT '/media/no_head_pic.gif' COMMENT '个人头像',
  `defendboss` varchar(200) DEFAULT NULL COMMENT '防老板键',
  `reg_time` int(11) NOT NULL COMMENT '注册时间',
  `reg_ip` bigint(20) DEFAULT NULL COMMENT '注册ip',
  `login_ip` varchar(100) DEFAULT NULL COMMENT '登录IP',
  `login_time` int(11) NOT NULL COMMENT '登录时间',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '注册类型(1微信, 2游客账号)，是否已设置密码',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_5`
--

LOCK TABLES `user_5` WRITE;
/*!40000 ALTER TABLE `user_5` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_5` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_6`
--

DROP TABLE IF EXISTS `user_6`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_6` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL COMMENT '用户UID',
  `bbs_uid` int(11) DEFAULT '0' COMMENT '论坛用户id',
  `user_name` varchar(50) NOT NULL COMMENT '用户名',
  `user_pwd` varchar(35) NOT NULL COMMENT '用户密码',
  `email` varchar(50) NOT NULL COMMENT '用户邮箱',
  `integral` int(11) NOT NULL DEFAULT '0' COMMENT '用户积分',
  `nick_name` varchar(50) DEFAULT NULL COMMENT '用户昵称',
  `true_name` varchar(50) DEFAULT NULL COMMENT '用户真实名',
  `sex` varchar(4) NOT NULL DEFAULT '男' COMMENT '性别',
  `id_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '证件类型',
  `id_card` varchar(20) DEFAULT NULL COMMENT '证件号码',
  `birthday` varchar(10) DEFAULT NULL COMMENT '生日',
  `telephone` varchar(20) DEFAULT NULL COMMENT '电话',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机',
  `address` varchar(200) DEFAULT NULL COMMENT '地址',
  `zipcode` int(6) DEFAULT NULL COMMENT '邮编',
  `level` tinyint(3) NOT NULL DEFAULT '0' COMMENT '等级',
  `qq` varchar(50) DEFAULT NULL,
  `msn` varchar(30) DEFAULT NULL,
  `question` varchar(200) DEFAULT NULL COMMENT '密保问题',
  `answer` varchar(200) DEFAULT NULL COMMENT '密保答案',
  `head_pic` varchar(200) NOT NULL DEFAULT '/media/no_head_pic.gif' COMMENT '个人头像',
  `defendboss` varchar(200) DEFAULT NULL COMMENT '防老板键',
  `reg_time` int(11) NOT NULL COMMENT '注册时间',
  `reg_ip` bigint(20) DEFAULT NULL COMMENT '注册ip',
  `login_ip` varchar(100) DEFAULT NULL COMMENT '登录IP',
  `login_time` int(11) NOT NULL COMMENT '登录时间',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '注册类型(1微信, 2游客账号)，是否已设置密码',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_6`
--

LOCK TABLES `user_6` WRITE;
/*!40000 ALTER TABLE `user_6` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_6` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_7`
--

DROP TABLE IF EXISTS `user_7`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_7` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL COMMENT '用户UID',
  `bbs_uid` int(11) DEFAULT '0' COMMENT '论坛用户id',
  `user_name` varchar(50) NOT NULL COMMENT '用户名',
  `user_pwd` varchar(35) NOT NULL COMMENT '用户密码',
  `email` varchar(50) NOT NULL COMMENT '用户邮箱',
  `integral` int(11) NOT NULL DEFAULT '0' COMMENT '用户积分',
  `nick_name` varchar(50) DEFAULT NULL COMMENT '用户昵称',
  `true_name` varchar(50) DEFAULT NULL COMMENT '用户真实名',
  `sex` varchar(4) NOT NULL DEFAULT '男' COMMENT '性别',
  `id_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '证件类型',
  `id_card` varchar(20) DEFAULT NULL COMMENT '证件号码',
  `birthday` varchar(10) DEFAULT NULL COMMENT '生日',
  `telephone` varchar(20) DEFAULT NULL COMMENT '电话',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机',
  `address` varchar(200) DEFAULT NULL COMMENT '地址',
  `zipcode` int(6) DEFAULT NULL COMMENT '邮编',
  `level` tinyint(3) NOT NULL DEFAULT '0' COMMENT '等级',
  `qq` varchar(50) DEFAULT NULL,
  `msn` varchar(30) DEFAULT NULL,
  `question` varchar(200) DEFAULT NULL COMMENT '密保问题',
  `answer` varchar(200) DEFAULT NULL COMMENT '密保答案',
  `head_pic` varchar(200) NOT NULL DEFAULT '/media/no_head_pic.gif' COMMENT '个人头像',
  `defendboss` varchar(200) DEFAULT NULL COMMENT '防老板键',
  `reg_time` int(11) NOT NULL COMMENT '注册时间',
  `reg_ip` bigint(20) DEFAULT NULL COMMENT '注册ip',
  `login_ip` varchar(100) DEFAULT NULL COMMENT '登录IP',
  `login_time` int(11) NOT NULL COMMENT '登录时间',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '注册类型(1微信, 2游客账号)，是否已设置密码',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_7`
--

LOCK TABLES `user_7` WRITE;
/*!40000 ALTER TABLE `user_7` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_7` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_8`
--

DROP TABLE IF EXISTS `user_8`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_8` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL COMMENT '用户UID',
  `bbs_uid` int(11) DEFAULT '0' COMMENT '论坛用户id',
  `user_name` varchar(50) NOT NULL COMMENT '用户名',
  `user_pwd` varchar(35) NOT NULL COMMENT '用户密码',
  `email` varchar(50) NOT NULL COMMENT '用户邮箱',
  `integral` int(11) NOT NULL DEFAULT '0' COMMENT '用户积分',
  `nick_name` varchar(50) DEFAULT NULL COMMENT '用户昵称',
  `true_name` varchar(50) DEFAULT NULL COMMENT '用户真实名',
  `sex` varchar(4) NOT NULL DEFAULT '男' COMMENT '性别',
  `id_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '证件类型',
  `id_card` varchar(20) DEFAULT NULL COMMENT '证件号码',
  `birthday` varchar(10) DEFAULT NULL COMMENT '生日',
  `telephone` varchar(20) DEFAULT NULL COMMENT '电话',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机',
  `address` varchar(200) DEFAULT NULL COMMENT '地址',
  `zipcode` int(6) DEFAULT '0' COMMENT '邮编',
  `level` tinyint(3) NOT NULL DEFAULT '0' COMMENT '等级',
  `qq` varchar(50) DEFAULT NULL,
  `msn` varchar(30) DEFAULT NULL,
  `question` varchar(200) DEFAULT NULL COMMENT '密保问题',
  `answer` varchar(200) DEFAULT NULL COMMENT '密保答案',
  `head_pic` varchar(200) NOT NULL DEFAULT '/media/no_head_pic.gif' COMMENT '个人头像',
  `defendboss` varchar(200) DEFAULT NULL COMMENT '防老板键',
  `reg_time` int(11) NOT NULL COMMENT '注册时间',
  `reg_ip` bigint(20) DEFAULT NULL COMMENT '注册ip',
  `login_ip` varchar(100) DEFAULT NULL COMMENT '登录IP',
  `login_time` int(11) NOT NULL COMMENT '登录时间',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '注册类型(1微信, 2游客账号)，是否已设置密码',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_8`
--

LOCK TABLES `user_8` WRITE;
/*!40000 ALTER TABLE `user_8` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_8` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_9`
--

DROP TABLE IF EXISTS `user_9`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_9` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL COMMENT '用户UID',
  `bbs_uid` int(11) DEFAULT '0' COMMENT '论坛用户id',
  `user_name` varchar(50) NOT NULL COMMENT '用户名',
  `user_pwd` varchar(35) NOT NULL COMMENT '用户密码',
  `email` varchar(50) NOT NULL COMMENT '用户邮箱',
  `integral` int(11) NOT NULL DEFAULT '0' COMMENT '用户积分',
  `nick_name` varchar(50) DEFAULT NULL COMMENT '用户昵称',
  `true_name` varchar(50) DEFAULT NULL COMMENT '用户真实名',
  `sex` varchar(4) NOT NULL DEFAULT '男' COMMENT '性别',
  `id_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '证件类型',
  `id_card` varchar(20) DEFAULT NULL COMMENT '证件号码',
  `birthday` varchar(10) DEFAULT NULL COMMENT '生日',
  `telephone` varchar(20) DEFAULT NULL COMMENT '电话',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机',
  `address` varchar(200) DEFAULT NULL COMMENT '地址',
  `zipcode` int(6) DEFAULT NULL COMMENT '邮编',
  `level` tinyint(3) NOT NULL DEFAULT '0' COMMENT '等级',
  `qq` varchar(50) DEFAULT NULL,
  `msn` varchar(30) DEFAULT NULL,
  `question` varchar(200) DEFAULT NULL COMMENT '密保问题',
  `answer` varchar(200) DEFAULT NULL COMMENT '密保答案',
  `head_pic` varchar(200) NOT NULL DEFAULT '/media/no_head_pic.gif' COMMENT '个人头像',
  `defendboss` varchar(200) DEFAULT NULL COMMENT '防老板键',
  `reg_time` int(11) NOT NULL COMMENT '注册时间',
  `reg_ip` bigint(20) DEFAULT NULL COMMENT '注册ip',
  `login_ip` varchar(100) DEFAULT NULL COMMENT '登录IP',
  `login_time` int(11) NOT NULL COMMENT '登录时间',
  `type` int(11) NOT NULL DEFAULT '0' COMMENT '注册类型(1微信, 2游客账号)，是否已设置密码',
  `state` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_9`
--

LOCK TABLES `user_9` WRITE;
/*!40000 ALTER TABLE `user_9` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_9` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `uid` bigint(50) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varchar(50) NOT NULL,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `user_name` (`user_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-10-30 13:59:28
