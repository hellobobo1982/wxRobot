-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2019-05-30 23:54:29
-- 服务器版本： 10.1.38-MariaDB
-- PHP 版本： 7.3.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `test`
--

-- --------------------------------------------------------

--
-- 表的结构 `anthority`
--

CREATE TABLE `anthority` (
  `username` varchar(32) NOT NULL,
  `password` varchar(32) NOT NULL,
  `pid` bigint(20) NOT NULL,
  `reference` varchar(32) NOT NULL,
  `registerTime` datetime NOT NULL,
  `flag` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `run_status`
--

CREATE TABLE `run_status` (
  `username` varchar(32) NOT NULL,
  `nickname` varchar(128) NOT NULL COMMENT '微信昵称',
  `headImgUrl` varchar(192) NOT NULL,
  `uin` varchar(64) NOT NULL,
  `uid` varchar(128) NOT NULL,
  `sid` varchar(64) NOT NULL,
  `skey` varchar(128) NOT NULL,
  `pass_ticket` varchar(256) NOT NULL,
  `uri` varchar(64) NOT NULL,
  `synckey` varchar(256) NOT NULL,
  `SyncKey_orig` varchar(256) NOT NULL,
  `lasttime` varchar(32) NOT NULL COMMENT '最后一次操作时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `wxmsg_receive`
--

CREATE TABLE `wxmsg_receive` (
  `username` varchar(32) CHARACTER SET utf8 NOT NULL,
  `id` bigint(32) NOT NULL,
  `uin` varchar(16) CHARACTER SET latin1 NOT NULL,
  `Content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `MsgType` int(11) NOT NULL,
  `Flag` tinyint(4) NOT NULL,
  `ReceiveTime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `wxmsg_sent`
--

CREATE TABLE `wxmsg_sent` (
  `id` bigint(32) NOT NULL,
  `uin` varchar(16) CHARACTER SET latin1 NOT NULL,
  `MsgType` int(11) NOT NULL,
  `Flag` tinyint(4) NOT NULL,
  `SentTime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- 转储表的索引
--

--
-- 表的索引 `run_status`
--
ALTER TABLE `run_status`
  ADD UNIQUE KEY `uni_code` (`username`,`uin`) USING BTREE;

--
-- 表的索引 `wxmsg_receive`
--
ALTER TABLE `wxmsg_receive`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `Id` (`id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `wxmsg_receive`
--
ALTER TABLE `wxmsg_receive`
  MODIFY `id` bigint(32) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;



-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2019-06-16 14:23:24
-- 服务器版本： 10.1.38-MariaDB
-- PHP 版本： 7.3.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `test`
--

-- --------------------------------------------------------

--
-- 表的结构 `agency`
--





CREATE TABLE `test`.`commission` ( `pid` VARCHAR(16) NOT NULL , `commission` DOUBLE NOT NULL , `updateTime` DATETIME NOT NULL ) ENGINE = InnoDB;
ALTER TABLE `commission` ADD UNIQUE(`pid`);


-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2019-06-22 18:42:41
-- 服务器版本： 10.1.38-MariaDB
-- PHP 版本： 7.3.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `test`
--

-- --------------------------------------------------------

--
-- 表的结构 `withdraw`
--

CREATE TABLE `withdraw` (
                            `requestId` int(11) NOT NULL,
                            `pid` varchar(16) NOT NULL,
                            `withdraw` double NOT NULL,
                            `requestTime` datetime NOT NULL COMMENT '申请时间',
                            `handleTime` datetime NOT NULL COMMENT '处理完成时间',
                            `status` int(11) NOT NULL COMMENT '0:未处理；1：已处理；-1：不处理',
                            `memo` int(11) NOT NULL COMMENT '备注'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转储表的索引
--

--
-- 表的索引 `withdraw`
--
ALTER TABLE `withdraw`
    ADD PRIMARY KEY (`requestId`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `withdraw`
--
ALTER TABLE `withdraw`
    MODIFY `requestId` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

