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
-- Database: `master`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `log`
-- 

CREATE TABLE `log` (
  `id` int(11) NOT NULL auto_increment,
  `subj_id` varchar(25) NOT NULL,
  `user_id` varchar(25) NOT NULL,
  `user_ip` varchar(25) NOT NULL,
  `mac` varchar(25) NOT NULL default '-',
  `online_time` datetime NOT NULL,
  `offline_time` datetime NOT NULL,
  `type` varchar(5) NOT NULL default 'C',
  `status` varchar(50) NOT NULL default 'offline',
  `approve` varchar(1) NOT NULL default 'N',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `log`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `owner`
-- 

CREATE TABLE `owner` (
  `user_id` varchar(50) NOT NULL,
  `subj_id` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 
-- Dumping data for table `owner`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `subj_info`
-- 

CREATE TABLE `subj_info` (
  `subj_id` varchar(25) NOT NULL,
  `code` varchar(25) default NULL,
  `name` varchar(100) NOT NULL,
  `year` varchar(10) default NULL,
  `term` varchar(10) default NULL,
  `status` varchar(3) NOT NULL default 'OFF',
  `db_name` varchar(25) NOT NULL,
  `printer` varchar(3) NOT NULL default 'OFF',
  `printer_name` varchar(200) default NULL,
  `content` varchar(3) NOT NULL default 'OFF',
  `source` varchar(3) NOT NULL default 'OFF',
  `max_source` int(11) NOT NULL default '5',
  `submit` varchar(3) NOT NULL default 'OFF',
  `link` varchar(3) NOT NULL default 'ON',
  `header` varchar(3) NOT NULL default 'OFF',
  `secure` varchar(3) NOT NULL default 'ON',
  PRIMARY KEY  (`subj_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 
-- Dumping data for table `subj_info`
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table `user_info`
-- 

CREATE TABLE `user_info` (
  `user_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `passwd` varchar(50) NOT NULL,
  `grp` varchar(50) NOT NULL,
  `email` varchar(100) default '-',
  `type` char(2) NOT NULL,
  `seat` varchar(20) default NULL,
  PRIMARY KEY  (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 
-- Dumping data for table `user_info`
-- 

INSERT INTO `user_info` VALUES ('superadmin', 'Super-Admin', 'superadmin', 'super admin', '', 'SA', '');
