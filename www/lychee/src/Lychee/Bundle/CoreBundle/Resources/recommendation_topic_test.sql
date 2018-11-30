-- phpMyAdmin SQL Dump
-- version 4.2.8
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 22, 2014 at 07:50 AM
-- Server version: 5.6.21
-- PHP Version: 5.6.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `ciyocon`
--

-- --------------------------------------------------------

--
-- Table structure for table `recommendation_topic_test`
--

CREATE TABLE IF NOT EXISTS `recommendation_topic_test` (
  `property` enum('zhai','meng','ran','fu','jian','ao') COLLATE utf8_unicode_ci NOT NULL,
  `score` smallint(6) NOT NULL,
  `topic_id` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `recommendation_topic_test`
--

INSERT INTO `recommendation_topic_test` (`property`, `score`, `topic_id`) VALUES
('zhai', 3, 25131),
('zhai', 3, 25159),
('zhai', 9, 25069),
('zhai', 9, 25231),
('zhai', 15, 25086),
('zhai', 15, 25094),
('meng', 3, 25073),
('meng', 3, 25076),
('meng', 9, 25074),
('meng', 9, 25077),
('meng', 15, 25078),
('meng', 15, 25251),
('ran', 3, 25079),
('ran', 3, 25169),
('ran', 9, 25080),
('ran', 9, 25148),
('ran', 15, 25189),
('ran', 15, 25225),
('fu', 3, 25106),
('fu', 3, 25116),
('fu', 9, 25088),
('fu', 9, 25235),
('fu', 15, 25100),
('fu', 15, 25102),
('jian', 3, 25097),
('jian', 3, 25109),
('jian', 9, 25085),
('jian', 9, 25098),
('jian', 15, 25091),
('jian', 15, 25249),
('ao', 3, 25090),
('ao', 3, 25093),
('ao', 9, 25089),
('ao', 9, 25132),
('ao', 15, 25123),
('ao', 15, 25240);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `recommendation_topic_test`
--
ALTER TABLE `recommendation_topic_test`
 ADD PRIMARY KEY (`property`,`score`,`topic_id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
