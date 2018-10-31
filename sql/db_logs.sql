-- MySQL dump 10.13  Distrib 5.7.12, for Linux (x86_64)
--
-- Host: localhost    Database: db_logs
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
-- Table structure for table `active_info_201810`
--

DROP TABLE IF EXISTS `active_info_201810`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `active_info_201810` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tdate` date NOT NULL,
  `user_name` varchar(50) NOT NULL,
  `agent_id` int(11) NOT NULL DEFAULT '0',
  `site_id` int(11) NOT NULL DEFAULT '0',
  `cplaceid` varchar(100) NOT NULL,
  `turn` tinyint(4) NOT NULL DEFAULT '0',
  `adid` varchar(20) NOT NULL,
  `game_id` int(11) NOT NULL DEFAULT '0',
  `server_id` int(11) NOT NULL DEFAULT '0',
  `active` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tdate_2` (`tdate`,`user_name`,`game_id`,`server_id`),
  KEY `tdate` (`tdate`),
  KEY `user_name` (`user_name`),
  KEY `agent_id` (`agent_id`),
  KEY `site_id` (`site_id`),
  KEY `adid` (`adid`),
  KEY `game_id` (`game_id`),
  KEY `server_id` (`server_id`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `active_info_201810`
--

LOCK TABLES `active_info_201810` WRITE;
/*!40000 ALTER TABLE `active_info_201810` DISABLE KEYS */;
/*!40000 ALTER TABLE `active_info_201810` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agent_reg_2018`
--

DROP TABLE IF EXISTS `agent_reg_2018`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agent_reg_2018` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) unsigned NOT NULL DEFAULT '0',
  `site_id` int(11) unsigned NOT NULL DEFAULT '0',
  `adid` varchar(10) DEFAULT '',
  `turn` tinyint(4) unsigned NOT NULL DEFAULT '1',
  `user_name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL DEFAULT '',
  `reg_ip` bigint(20) unsigned NOT NULL,
  `reg_time` int(11) unsigned NOT NULL,
  `referer_url` varchar(255) DEFAULT '',
  `game_id` smallint(11) unsigned NOT NULL DEFAULT '0',
  `server_id` mediumint(9) NOT NULL DEFAULT '0',
  `login_time` int(11) unsigned NOT NULL DEFAULT '0',
  `login_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '登录次数',
  `ext1` varchar(50) DEFAULT '',
  `ext2` varchar(50) DEFAULT '',
  `ext3` varchar(50) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `agent_id` (`agent_id`),
  KEY `site_id` (`site_id`),
  KEY `login_time` (`login_time`),
  KEY `reg_time` (`reg_time`),
  KEY `reg_ip` (`reg_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agent_reg_2018`
--

LOCK TABLES `agent_reg_2018` WRITE;
/*!40000 ALTER TABLE `agent_reg_2018` DISABLE KEYS */;
/*!40000 ALTER TABLE `agent_reg_2018` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `game_login_info_201810`
--

DROP TABLE IF EXISTS `game_login_info_201810`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_login_info_201810` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varchar(50) NOT NULL,
  `agent_id` int(11) unsigned NOT NULL DEFAULT '0',
  `site_id` int(11) unsigned NOT NULL DEFAULT '0',
  `cplaceid` varchar(50) DEFAULT '',
  `adid` varchar(50) DEFAULT '',
  `turn` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `game_id` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `server_id` mediumint(9) NOT NULL DEFAULT '0',
  `ip` int(11) unsigned NOT NULL,
  `login_time` int(11) unsigned NOT NULL,
  `reg_time` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_name` (`user_name`),
  KEY `agent_id` (`agent_id`),
  KEY `game_id` (`game_id`),
  KEY `server_id` (`server_id`),
  KEY `ip` (`ip`),
  KEY `login_time` (`login_time`),
  KEY `reg_time` (`reg_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `game_login_info_201810`
--

LOCK TABLES `game_login_info_201810` WRITE;
/*!40000 ALTER TABLE `game_login_info_201810` DISABLE KEYS */;
/*!40000 ALTER TABLE `game_login_info_201810` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `game_login_info_sum`
--

DROP TABLE IF EXISTS `game_login_info_sum`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_login_info_sum` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varchar(50) NOT NULL DEFAULT '',
  `game_id` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `login_time` date NOT NULL,
  `reg_time` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_name`) USING BTREE,
  KEY `idx_login` (`login_time`) USING BTREE,
  KEY `idx_reg` (`reg_time`) USING BTREE,
  KEY `idx_game` (`game_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `game_login_info_sum`
--

LOCK TABLES `game_login_info_sum` WRITE;
/*!40000 ALTER TABLE `game_login_info_sum` DISABLE KEYS */;
/*!40000 ALTER TABLE `game_login_info_sum` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-10-30 12:04:23
