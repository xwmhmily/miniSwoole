-- phpMyAdmin SQL Dump
-- version 4.7.9
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: 2019-03-09 15:22:38
-- 服务器版本： 5.6.25
-- PHP Version: 7.1.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tongshang`
--

-- --------------------------------------------------------

--
-- 表的结构 `sl_user`
--

CREATE TABLE `sl_user` (
  `id` int(11) NOT NULL,
  `username` varchar(30) NOT NULL,
  `password` varchar(32) NOT NULL,
  `status` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户表';

--
-- 转存表中的数据 `sl_user`
--

INSERT INTO `sl_user` (`id`, `username`, `password`, `status`) VALUES
(1, 'IAMCARRY', '0d08a0f4fe10a71de5bf4bc93436d811', 0),
(2, 'IAMCARRY', '0d08a0f4fe10a71de5bf4bc93436d811', 0),
(3, 'IAMCARRY', '0d08a0f4fe10a71de5bf4bc93436d811', 0),
(4, 'IAMCARRY', '0d08a0f4fe10a71de5bf4bc93436d811', 0),
(5, 'IAMCARRY', '0d08a0f4fe10a71de5bf4bc93436d811', 0);

-- --------------------------------------------------------

--
-- 表的结构 `sl_user_38`
--

CREATE TABLE `sl_user_38` (
  `id` int(11) NOT NULL,
  `username` varchar(30) NOT NULL,
  `password` varchar(32) NOT NULL,
  `status` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户表';

--
-- 转存表中的数据 `sl_user_38`
--

INSERT INTO `sl_user_38` (`id`, `username`, `password`, `status`) VALUES
(1, 'Lily', 'e10adc3949ba59abbe56e057f20f883e', 1);

-- --------------------------------------------------------

--
-- 表的结构 `sl_user_52`
--

CREATE TABLE `sl_user_52` (
  `id` int(11) NOT NULL,
  `username` varchar(30) NOT NULL,
  `password` varchar(32) NOT NULL,
  `status` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户表';

--
-- 转存表中的数据 `sl_user_52`
--

INSERT INTO `sl_user_52` (`id`, `username`, `password`, `status`) VALUES
(1, 'Deny', 'e10adc3949ba59abbe56e057f20f883e', 1),
(2, 'Curry', 'e10adc3949ba59abbe56e057f20f883e', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `sl_user`
--
ALTER TABLE `sl_user`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sl_user_38`
--
ALTER TABLE `sl_user_38`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sl_user_52`
--
ALTER TABLE `sl_user_52`
  ADD PRIMARY KEY (`id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `sl_user`
--
ALTER TABLE `sl_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `sl_user_38`
--
ALTER TABLE `sl_user_38`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `sl_user_52`
--
ALTER TABLE `sl_user_52`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
