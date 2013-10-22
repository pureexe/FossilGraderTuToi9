-- phpMyAdmin SQL Dump
-- version 2.10.3
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Oct 16, 2012 at 08:07 AM
-- Server version: 5.0.51
-- PHP Version: 5.2.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- 
-- Database: `olympic`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `grd_queue`
-- 

CREATE TABLE `grd_queue` (
  `q_id` int(10) unsigned NOT NULL auto_increment,
  `user_id` varchar(100) NOT NULL,
  `prob_id` varchar(100) NOT NULL,
  `sub_num` int(10) unsigned default '0',
  `compiler` varchar(100) default NULL,
  PRIMARY KEY  (`q_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `grd_queue`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `grd_status`
-- 

CREATE TABLE `grd_status` (
  `user_id` varchar(100) NOT NULL,
  `prob_id` varchar(100) NOT NULL,
  `res_id` int(10) unsigned NOT NULL default '0',
  `score` int(10) unsigned default '0',
  `compiler_msg` text,
  `grading_msg` varchar(100) default NULL,
  `compiler` varchar(100) default NULL,
  `host_index` varchar(100) default NULL,
  `compiling` varchar(100) default NULL,
  `sub_num` int(11) default '0',
  PRIMARY KEY  (`user_id`,`prob_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 
-- Dumping data for table `grd_status`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `prob_info`
-- 

CREATE TABLE `prob_info` (
  `prob_id` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL default '',
  `avail` char(3) NOT NULL default 'OFF',
  `prob_order` int(10) unsigned default NULL,
  `score` varchar(100) NOT NULL default '0',
  `evaluator` varchar(100) NOT NULL default '../compare_unsort.exe',
  `timelimit` float NOT NULL default '1',
  `memorylimit` float NOT NULL default '64',
  `color` varchar(7) default NULL,
  `time` varchar(50) default NULL,
  `description` varchar(500) default NULL,
  `ready` varchar(10) NOT NULL default 'unready',
  PRIMARY KEY  (`prob_id`),
  KEY `ordering` (`prob_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 
-- Dumping data for table `prob_info`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `res_desc`
-- 

CREATE TABLE `res_desc` (
  `res_id` int(10) unsigned NOT NULL auto_increment,
  `res_text` varchar(45) NOT NULL default '',
  PRIMARY KEY  (`res_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

-- 
-- Dumping data for table `res_desc`
-- 

INSERT INTO `res_desc` VALUES (1, 'in queue');
INSERT INTO `res_desc` VALUES (2, 'grading');
INSERT INTO `res_desc` VALUES (3, 'accepted');
INSERT INTO `res_desc` VALUES (4, 'rejected');

-- --------------------------------------------------------

-- 
-- Table structure for table `submission`
-- 

CREATE TABLE `submission` (
  `user_id` varchar(100) NOT NULL,
  `prob_id` varchar(100) NOT NULL,
  `sub_num` int(11) NOT NULL default '0',
  `time` datetime default '0000-00-00 00:00:00',
  `code` mediumtext NOT NULL,
  PRIMARY KEY  (`user_id`,`prob_id`,`sub_num`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 
-- Dumping data for table `submission`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `user_info`
-- 

CREATE TABLE `user_info` (
  `user_id` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `passwd` varchar(100) NOT NULL,
  `grp` varchar(100) default NULL,
  `email` varchar(100) NOT NULL default '-',
  `type` char(1) NOT NULL,
  `seat` varchar(20) default NULL,
  PRIMARY KEY  (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 
-- Dumping data for table `user_info`
-- 

