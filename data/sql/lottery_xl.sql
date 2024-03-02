/*
Navicat MySQL Data Transfer

Source Server         : me（120.77.157.40）
Source Server Version : 50641
Source Host           : 120.77.157.40:3306
Source Database       : lottery_xl

Target Server Type    : MYSQL
Target Server Version : 50641
File Encoding         : 65001

Date: 2019-05-27 09:34:54
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `blast_played_group`
-- ----------------------------
DROP TABLE IF EXISTS `blast_played_group`;
CREATE TABLE `blast_played_group` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `enable` tinyint(1) NOT NULL DEFAULT '1',
  `type` tinyint(4) NOT NULL COMMENT 'ssc_type.type',
  `groupName` varchar(32) CHARACTER SET utf8 NOT NULL,
  `sort` int(4) NOT NULL,
  `bdwEnable` tinyint(1) NOT NULL DEFAULT '0',
  `android` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `enable` (`enable`,`type`,`groupName`,`sort`,`bdwEnable`)
) ENGINE=MyISAM AUTO_INCREMENT=107 DEFAULT CHARSET=latin1 COMMENT='玩法组表';

-- ----------------------------
-- Records of blast_played_group
-- ----------------------------
INSERT INTO `blast_played_group` VALUES ('1', '1', '1', '五星', '1', '0', '0');
INSERT INTO `blast_played_group` VALUES ('2', '1', '1', '前三', '3', '0', '1');
INSERT INTO `blast_played_group` VALUES ('3', '1', '1', '中三', '4', '0', '0');
INSERT INTO `blast_played_group` VALUES ('4', '1', '1', '前二', '6', '0', '0');
INSERT INTO `blast_played_group` VALUES ('5', '1', '1', '后二', '7', '0', '0');
INSERT INTO `blast_played_group` VALUES ('6', '1', '1', '定位胆', '8', '0', '0');
INSERT INTO `blast_played_group` VALUES ('7', '1', '1', '不定胆', '9', '1', '0');
INSERT INTO `blast_played_group` VALUES ('8', '1', '1', '大小单双', '10', '0', '0');
INSERT INTO `blast_played_group` VALUES ('9', '1', '2', '任选复试', '5', '0', '0');
INSERT INTO `blast_played_group` VALUES ('10', '1', '2', '前二', '0', '0', '0');
INSERT INTO `blast_played_group` VALUES ('11', '1', '2', '前三', '1', '0', '0');
INSERT INTO `blast_played_group` VALUES ('12', '1', '3', '直选', '0', '0', '0');
INSERT INTO `blast_played_group` VALUES ('13', '1', '3', '组选', '1', '0', '0');
INSERT INTO `blast_played_group` VALUES ('14', '1', '3', '二码', '3', '0', '0');
INSERT INTO `blast_played_group` VALUES ('80', '1', '2', '前一', '0', '0', '0');
INSERT INTO `blast_played_group` VALUES ('16', '1', '3', '定位胆', '5', '0', '0');
INSERT INTO `blast_played_group` VALUES ('17', '1', '3', '不定位', '2', '1', '0');
INSERT INTO `blast_played_group` VALUES ('18', '1', '3', '大小单双', '4', '0', '0');
INSERT INTO `blast_played_group` VALUES ('19', '1', '4', '任选', '0', '0', '0');
INSERT INTO `blast_played_group` VALUES ('20', '1', '4', '选一', '0', '0', '0');
INSERT INTO `blast_played_group` VALUES ('21', '1', '4', '选二', '0', '0', '0');
INSERT INTO `blast_played_group` VALUES ('22', '1', '4', '选三', '0', '0', '0');
INSERT INTO `blast_played_group` VALUES ('59', '1', '5', '三星', '3', '0', '1');
INSERT INTO `blast_played_group` VALUES ('58', '1', '5', '四星', '2', '0', '1');
INSERT INTO `blast_played_group` VALUES ('26', '1', '6', '猜冠军', '1', '0', '0');
INSERT INTO `blast_played_group` VALUES ('27', '1', '6', '猜冠亚军', '2', '0', '0');
INSERT INTO `blast_played_group` VALUES ('28', '1', '6', '猜前三名', '3', '0', '0');
INSERT INTO `blast_played_group` VALUES ('29', '1', '6', '定位胆选', '4', '0', '0');
INSERT INTO `blast_played_group` VALUES ('38', '1', '8', '任选', '0', '0', '0');
INSERT INTO `blast_played_group` VALUES ('39', '1', '9', '和值', '9', '0', '0');
INSERT INTO `blast_played_group` VALUES ('40', '1', '9', '三同号通选', '3', '0', '0');
INSERT INTO `blast_played_group` VALUES ('41', '1', '9', '三同号单选', '2', '0', '0');
INSERT INTO `blast_played_group` VALUES ('42', '1', '9', '二同号复选', '6', '0', '0');
INSERT INTO `blast_played_group` VALUES ('43', '1', '9', '二同号单选', '7', '0', '0');
INSERT INTO `blast_played_group` VALUES ('44', '1', '9', '三不同号', '5', '0', '0');
INSERT INTO `blast_played_group` VALUES ('45', '1', '9', '二不同号', '8', '0', '0');
INSERT INTO `blast_played_group` VALUES ('46', '1', '9', '三连号通选', '4', '0', '0');
INSERT INTO `blast_played_group` VALUES ('50', '1', '2', '单式', '2', '0', '0');
INSERT INTO `blast_played_group` VALUES ('66', '1', '1', '四星', '2', '0', '1');
INSERT INTO `blast_played_group` VALUES ('60', '1', '5', '三星组选', '4', '0', '1');
INSERT INTO `blast_played_group` VALUES ('61', '1', '5', '前二', '6', '0', '1');
INSERT INTO `blast_played_group` VALUES ('62', '1', '5', '后二', '7', '0', '0');
INSERT INTO `blast_played_group` VALUES ('63', '1', '5', '五星', '1', '0', '1');
INSERT INTO `blast_played_group` VALUES ('64', '1', '5', '不定胆', '7', '1', '0');
INSERT INTO `blast_played_group` VALUES ('65', '1', '5', '大小单双', '8', '0', '0');
INSERT INTO `blast_played_group` VALUES ('67', '1', '6', '两面', '5', '0', '0');
INSERT INTO `blast_played_group` VALUES ('68', '1', '6', '龙虎', '6', '0', '0');
INSERT INTO `blast_played_group` VALUES ('69', '1', '6', '冠亚季选一', '7', '0', '0');
INSERT INTO `blast_played_group` VALUES ('70', '1', '6', '冠亚组合', '8', '0', '0');
INSERT INTO `blast_played_group` VALUES ('72', '1', '5', '定位胆', '6', '0', '0');
INSERT INTO `blast_played_group` VALUES ('73', '1', '5', '趣味', '10', '0', '0');
INSERT INTO `blast_played_group` VALUES ('74', '1', '1', '趣味', '12', '0', '0');
INSERT INTO `blast_played_group` VALUES ('75', '1', '2', '趣味型', '7', '0', '0');
INSERT INTO `blast_played_group` VALUES ('76', '1', '1', '任选', '11', '0', '0');
INSERT INTO `blast_played_group` VALUES ('77', '1', '2', '定位胆', '3', '0', '0');
INSERT INTO `blast_played_group` VALUES ('78', '1', '2', '不定位', '4', '1', '0');
INSERT INTO `blast_played_group` VALUES ('79', '1', '2', '任选单试', '6', '0', '0');
INSERT INTO `blast_played_group` VALUES ('81', '1', '11', '特别号', '1', '0', '0');
INSERT INTO `blast_played_group` VALUES ('82', '1', '11', '生肖头尾', '2', '0', '0');
INSERT INTO `blast_played_group` VALUES ('84', '1', '11', '正码平码', '4', '0', '0');
INSERT INTO `blast_played_group` VALUES ('83', '1', '11', '波色', '3', '0', '0');
INSERT INTO `blast_played_group` VALUES ('85', '1', '11', '平特肖尾', '5', '0', '0');
INSERT INTO `blast_played_group` VALUES ('86', '1', '11', '连肖', '6', '0', '0');
INSERT INTO `blast_played_group` VALUES ('87', '1', '11', '连尾', '7', '0', '0');
INSERT INTO `blast_played_group` VALUES ('88', '1', '11', '连码', '8', '0', '0');
INSERT INTO `blast_played_group` VALUES ('89', '1', '11', '自选不中', '9', '0', '0');
INSERT INTO `blast_played_group` VALUES ('47', '1', '9', '二面', '1', '0', '0');
INSERT INTO `blast_played_group` VALUES ('99', '1', '1', '后三', '5', '0', '0');
INSERT INTO `blast_played_group` VALUES ('100', '1', '95', '海南玩法', '1', '0', '0');
INSERT INTO `blast_played_group` VALUES ('105', '1', '8', '两面', '1', '0', '0');
INSERT INTO `blast_played_group` VALUES ('102', '1', '1', '海南玩法', '2', '0', '0');
INSERT INTO `blast_played_group` VALUES ('103', '1', '96', '海南玩法', '1', '0', '0');
INSERT INTO `blast_played_group` VALUES ('104', '1', '1', '快打玩法', '2', '0', '0');
INSERT INTO `blast_played_group` VALUES ('106', '1', '9', '鱼虾蟹', '0', '0', '0');

-- ----------------------------
-- Table structure for `lt_admin`
-- ----------------------------
DROP TABLE IF EXISTS `lt_admin`;
CREATE TABLE `lt_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `auth_key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password_reset_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pay_time` datetime DEFAULT NULL COMMENT '缴费日期',
  `desc` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '描述',
  `status` smallint(6) NOT NULL DEFAULT '10',
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of lt_admin
-- ----------------------------
INSERT INTO `lt_admin` VALUES ('1', 'admin', '9Rd593fzYYyWj-DRStnttFtZQ984i0GX', '$2y$13$NNQcbWHrJEFvmqnsP5V/dOHtDHZ7MxcRSLDczqR/KrQdQDLzy8Syq', null, 'admin@126.com', null, '超级管理员', '10', '1528708403', '1558770686');
INSERT INTO `lt_admin` VALUES ('2', 'gaozi2017', 'uTVNSa5TNFCmKLg1R0R7IYqBDPeGX337', '$2y$13$wP2ljuognVqqFrqgaJzCfuk.7aIudivxil7XAddGUn.nYGk146LjC', null, 'wangyegao@126.com', null, '终身会员', '10', '1528774000', '1558770862');
INSERT INTO `lt_admin` VALUES ('3', 'TedGod', '7vIMTdQKSYDfGju4qQKpvinYLWWoVkEF', '$2y$13$/SViy1IA5HApzXuyDmujI.biru1LOvg/MACkxz94l9jINj4ObOkgS', null, '379879537@qq.com', '2019-05-25 15:45:05', '终身会员', '10', '1529855853', '1558770669');
INSERT INTO `lt_admin` VALUES ('10', 'as01', 'CP4X_q9OtnkxVM7ZbtZqCINcNeOOIx8X', '$2y$13$/K9rGNrh1U2.Vk37yAmi6.Qs1oX3BL1f5Z2lDhE3ueIpAwoMRPTKm', null, 'as01@126.com', null, '测试演示账号', '10', '1556560311', '1558770725');
INSERT INTO `lt_admin` VALUES ('11', 'gaozi2018', 'b2cia-Fi-PVltnLFJb93mGrPPZRZGsRl', '$2y$13$kUhFft9A6HdffFGorKbe6u4wi9kXiNunFsnixr95XZCCwrVTrUPJ2', null, 'gaozi2018@126.com', null, '终身会员', '10', '1558359930', '1558770792');
INSERT INTO `lt_admin` VALUES ('12', 'aa03', 'rCQUKM4vPOCL0UcdU_kaqAeK6lVLaCkT', '$2y$13$1fvDs3mgpJNk2jxnrmZJN.YVeEngIv8OuvdcHog8ZZAjM6g9IfrFa', null, 'aa03@126.com', '2019-05-20 15:38:17', '做业务，每个月20号，最后一次缴费：2019-05-20，5月份已经缴清', '10', '1558510207', '1558511107');
INSERT INTO `lt_admin` VALUES ('13', 'aa05', '39f-wUD07vCswdwjDt_eRB_0NjMjWK1T', '$2y$13$l52/jRwyJyTP8Lttw/crb.rkCcktRPPakRvURm9iuQuNFuD1WfeYa', null, 'aa05@126.com', '2019-05-01 15:33:29', '每个月1号，4月份还欠1000元，下次缴费日期5月1号', '10', '1558515824', '1558515824');
INSERT INTO `lt_admin` VALUES ('14', 'aa06', 't-n-oUs-Or1ZT7_8WlVLE3Gty1E-GFGd', '$2y$13$4IcAK/2103Xho1C3V0gmgu.PIP6EixKCPQNNM3CNAdtehlB6xbIzq', null, 'aa06@126.com', '2019-05-22 12:00:00', '每个月22号，最后一次缴费：2019-05-22（2500元）', '10', '1558515890', '1558515890');
INSERT INTO `lt_admin` VALUES ('15', '六六大顺888', 'okdGF7-MVYlxjf8miL76TpjrYSZWoKbo', '$2y$13$r66KWr/fIC1iMmGNg1lWtOty6QZiowQO9oXbA0io8AxABKouMeYf2', null, '666888@126.com', null, '免费会员', '10', '1558718408', '1558770822');
INSERT INTO `lt_admin` VALUES ('16', '一路发888', '4qpuPLyvelppmc2fTJfJYLYjmz6gKagy', '$2y$13$uIeh/1HEiIFaIyGdVE5dL.wQttWeupZ0GOGjeZSj4oCSUO1halAua', null, '514320@126.com', null, '免费会员', '10', '1558718613', '1558770834');
INSERT INTO `lt_admin` VALUES ('17', 'babo', '05qCBO8x5HnFLQ4Qd_eO88aj25OYXmex', '$2y$13$RltaJFEfTit.mt54oVjCGuq2Y23VvYtnbcT9od8.X7qP/cEn0J4tm', null, 'babo@126.com', null, '免费会员', '10', '1558719940', '1558770842');
INSERT INTO `lt_admin` VALUES ('18', 'aa07', '7vcg4zpaiJ7h5GmqW0hknmKzJgaHvDUQ', '$2y$13$pVXRae1iftZL9orAIbkIlOotqEynYU17N/v9RJrWYxkzW0V0fS8em', null, 'aa07@126.com', '2019-05-25 15:00:00', '每个月25号，5月份已经缴清，最后一次缴费：2019-05-25', '10', '1558766644', '1558766644');

-- ----------------------------
-- Table structure for `lt_admin_log`
-- ----------------------------
DROP TABLE IF EXISTS `lt_admin_log`;
CREATE TABLE `lt_admin_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `route` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user_agent` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `gets` text COLLATE utf8_unicode_ci,
  `posts` text COLLATE utf8_unicode_ci NOT NULL,
  `admin_id` int(11) NOT NULL,
  `admin_email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ip` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1504 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of lt_admin_log
-- ----------------------------
INSERT INTO `lt_admin_log` VALUES ('1', 'forum/user/view', 'http://lt.yjjytech.com/forum/user/view.html', 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.98 Safari/537.36 LBBROWSER', '[]', '[]', '16', '514320@126.com', '36.101.203.185', '1558811236', '1558811236');

-- ----------------------------
-- Table structure for `lt_auth_assignment`
-- ----------------------------
DROP TABLE IF EXISTS `lt_auth_assignment`;
CREATE TABLE `lt_auth_assignment` (
  `item_name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_name`,`user_id`),
  KEY `auth_assignment_user_id_idx` (`user_id`),
  CONSTRAINT `lt_auth_assignment_ibfk_1` FOREIGN KEY (`item_name`) REFERENCES `lt_auth_item` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of lt_auth_assignment
-- ----------------------------
INSERT INTO `lt_auth_assignment` VALUES ('Admin', '1', '1457092343', null);
INSERT INTO `lt_auth_assignment` VALUES ('member', '3', '1529855865', '1530370334');
INSERT INTO `lt_auth_assignment` VALUES ('member', '4', '1530282769', '1530282769');
INSERT INTO `lt_auth_assignment` VALUES ('member', '5', '1530370334', '1530370334');
INSERT INTO `lt_auth_assignment` VALUES ('member', '6', '1530581937', '1530581937');
INSERT INTO `lt_auth_assignment` VALUES ('member', '7', '1551020575', '1551020575');
INSERT INTO `lt_auth_assignment` VALUES ('member', '8', '1553776811', '1553776811');
INSERT INTO `lt_auth_assignment` VALUES ('member', '9', '1555267557', '1555267557');
INSERT INTO `lt_auth_assignment` VALUES ('收费会员', '10', '1556562694', null);
INSERT INTO `lt_auth_assignment` VALUES ('收费会员', '11', '1558530662', null);
INSERT INTO `lt_auth_assignment` VALUES ('收费会员', '12', '1558510207', '1558510207');
INSERT INTO `lt_auth_assignment` VALUES ('收费会员', '13', '1558515824', '1558515824');
INSERT INTO `lt_auth_assignment` VALUES ('收费会员', '14', '1558515890', '1558515890');
INSERT INTO `lt_auth_assignment` VALUES ('收费会员', '15', '1558718408', '1558718408');
INSERT INTO `lt_auth_assignment` VALUES ('收费会员', '16', '1558718613', '1558718613');
INSERT INTO `lt_auth_assignment` VALUES ('收费会员', '17', '1558719941', '1558719941');
INSERT INTO `lt_auth_assignment` VALUES ('收费会员', '18', '1558766644', '1558766644');
INSERT INTO `lt_auth_assignment` VALUES ('收费会员', '2', '1555267557', '1555267557');

-- ----------------------------
-- Table structure for `lt_auth_item`
-- ----------------------------
DROP TABLE IF EXISTS `lt_auth_item`;
CREATE TABLE `lt_auth_item` (
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `type` smallint(6) NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `rule_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data` blob,
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`name`),
  KEY `rule_name` (`rule_name`),
  KEY `idx-auth_item-type` (`type`),
  CONSTRAINT `lt_auth_item_ibfk_1` FOREIGN KEY (`rule_name`) REFERENCES `lt_auth_rule` (`name`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of lt_auth_item
-- ----------------------------
INSERT INTO `lt_auth_item` VALUES ('/admin/*', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/assignment/*', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/assignment/assign', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/assignment/create', '2', null, null, null, '1457521995', '1457521995');
INSERT INTO `lt_auth_item` VALUES ('/admin/assignment/delete', '2', null, null, null, '1458010804', '1458010804');
INSERT INTO `lt_auth_item` VALUES ('/admin/assignment/index', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/assignment/search', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/assignment/update', '2', null, null, null, '1458010804', '1458010804');
INSERT INTO `lt_auth_item` VALUES ('/admin/assignment/view', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/default/*', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/default/index', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/log/*', '2', null, null, null, '1468288689', '1468288689');
INSERT INTO `lt_auth_item` VALUES ('/admin/log/index', '2', null, null, null, '1468288689', '1468288689');
INSERT INTO `lt_auth_item` VALUES ('/admin/log/view', '2', null, null, null, '1468288689', '1468288689');
INSERT INTO `lt_auth_item` VALUES ('/admin/menu/*', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/menu/create', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/menu/delete', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/menu/index', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/menu/update', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/menu/view', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/permission/*', '2', null, null, null, '1457948575', '1457948575');
INSERT INTO `lt_auth_item` VALUES ('/admin/permission/assign', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/permission/create', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/permission/delete', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/permission/index', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/permission/search', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/permission/update', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/permission/view', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/role/*', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/role/assign', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/role/create', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/role/delete', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/role/index', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/role/search', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/role/update', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/role/view', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/route/*', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/route/assign', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/route/create', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/route/index', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/route/refresh', '2', null, null, null, '1457947924', '1457947924');
INSERT INTO `lt_auth_item` VALUES ('/admin/route/search', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/rule/*', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/rule/create', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/rule/delete', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/rule/index', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/rule/update', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/admin/rule/view', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/api/*', '2', null, null, null, '1530518916', '1530518916');
INSERT INTO `lt_auth_item` VALUES ('/api/index/*', '2', null, null, null, '1530518916', '1530518916');
INSERT INTO `lt_auth_item` VALUES ('/api/index/tz', '2', null, null, null, '1530515561', '1530515561');
INSERT INTO `lt_auth_item` VALUES ('/cron/index/*', '2', null, null, null, '1529069207', '1529069207');
INSERT INTO `lt_auth_item` VALUES ('/cron/index/grab-kj-data', '2', null, null, null, '1529152858', '1529152858');
INSERT INTO `lt_auth_item` VALUES ('/cron/index/grab-kj-data-one', '2', null, null, null, '1529152859', '1529152859');
INSERT INTO `lt_auth_item` VALUES ('/cron/index/grab-qxc-kj-data', '2', null, null, null, '1529152859', '1529152859');
INSERT INTO `lt_auth_item` VALUES ('/cron/index/insert-ssc-dws-hz-nums', '2', null, null, null, '1531411104', '1531411104');
INSERT INTO `lt_auth_item` VALUES ('/cron/index/update-code', '2', null, null, null, '1529152859', '1529152859');
INSERT INTO `lt_auth_item` VALUES ('/cron/index/update-null-codes', '2', null, null, null, '1529152859', '1529152859');
INSERT INTO `lt_auth_item` VALUES ('/debug/*', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/debug/default/*', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/debug/default/db-explain', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/debug/default/download-mail', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/debug/default/index', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/debug/default/toolbar', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/debug/default/view', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/forum/betting-records/*', '2', null, null, null, '1528714001', '1528714001');
INSERT INTO `lt_auth_item` VALUES ('/forum/betting-records/cancel-order', '2', null, null, null, '1529655476', '1529655476');
INSERT INTO `lt_auth_item` VALUES ('/forum/betting-records/create', '2', null, null, null, '1528714403', '1528714403');
INSERT INTO `lt_auth_item` VALUES ('/forum/betting-records/delete', '2', null, null, null, '1528714403', '1528714403');
INSERT INTO `lt_auth_item` VALUES ('/forum/betting-records/error', '2', null, null, null, '1528714403', '1528714403');
INSERT INTO `lt_auth_item` VALUES ('/forum/betting-records/index', '2', null, null, null, '1528713818', '1528713818');
INSERT INTO `lt_auth_item` VALUES ('/forum/betting-records/pre-date-profits', '2', null, null, null, '1548834043', '1548834043');
INSERT INTO `lt_auth_item` VALUES ('/forum/betting-records/reverse-tz-now', '2', null, null, null, '1548436325', '1548436325');
INSERT INTO `lt_auth_item` VALUES ('/forum/betting-records/sys-tz-list', '2', null, null, null, '1548659831', '1548659831');
INSERT INTO `lt_auth_item` VALUES ('/forum/betting-records/tz-now', '2', null, null, null, '1548261798', '1548261798');
INSERT INTO `lt_auth_item` VALUES ('/forum/betting-records/update', '2', null, null, null, '1528714403', '1528714403');
INSERT INTO `lt_auth_item` VALUES ('/forum/betting-records/view', '2', null, null, null, '1528713818', '1528713818');
INSERT INTO `lt_auth_item` VALUES ('/forum/index/get-balance', '2', null, null, null, '1528713846', '1528713846');
INSERT INTO `lt_auth_item` VALUES ('/forum/index/index', '2', null, null, null, '1528713846', '1528713846');
INSERT INTO `lt_auth_item` VALUES ('/forum/index/syn-balance', '2', null, null, null, '1528713846', '1528713846');
INSERT INTO `lt_auth_item` VALUES ('/forum/index/test', '2', null, null, null, '1528713845', '1528713845');
INSERT INTO `lt_auth_item` VALUES ('/forum/playways-auth/*', '2', null, null, null, '1555269571', '1555269571');
INSERT INTO `lt_auth_item` VALUES ('/forum/playways-auth/create', '2', null, null, null, '1555144139', '1555144139');
INSERT INTO `lt_auth_item` VALUES ('/forum/playways-auth/delete', '2', null, null, null, '1555144139', '1555144139');
INSERT INTO `lt_auth_item` VALUES ('/forum/playways-auth/index', '2', null, null, null, '1555144139', '1555144139');
INSERT INTO `lt_auth_item` VALUES ('/forum/playways-auth/update', '2', null, null, null, '1555144139', '1555144139');
INSERT INTO `lt_auth_item` VALUES ('/forum/playways-auth/view', '2', null, null, null, '1555144139', '1555144139');
INSERT INTO `lt_auth_item` VALUES ('/forum/playways/create', '2', null, null, null, '1555144153', '1555144153');
INSERT INTO `lt_auth_item` VALUES ('/forum/playways/delete', '2', null, null, null, '1555144153', '1555144153');
INSERT INTO `lt_auth_item` VALUES ('/forum/playways/index', '2', null, null, null, '1555144153', '1555144153');
INSERT INTO `lt_auth_item` VALUES ('/forum/playways/update', '2', null, null, null, '1555144153', '1555144153');
INSERT INTO `lt_auth_item` VALUES ('/forum/playways/view', '2', null, null, null, '1555144153', '1555144153');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-ds-static/index', '2', null, null, null, '1533808573', '1533808573');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-ds-static/view', '2', null, null, null, '1533808597', '1533808597');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-ds-yl/index', '2', null, null, null, '1533808581', '1533808581');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-ds-yl/view', '2', null, null, null, '1533808589', '1533808589');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-dw-hz-static/*', '2', null, null, null, '1529069179', '1529069179');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-dw-hz-static/echarts', '2', null, null, null, '1529075146', '1529075146');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-dw-hz-static/echarts?positions=1_2', '2', null, null, null, '1529220139', '1529220139');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-dw-hz-static/index', '2', null, null, null, '1529069179', '1529069179');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-dw-hz-static/view', '2', null, null, null, '1529069179', '1529069179');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-dw-hz-yl/*', '2', null, null, null, '1529326315', '1529326315');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-dw-hz-yl/index', '2', null, null, null, '1529326314', '1529326314');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-dw-hz-yl/view', '2', null, null, null, '1529326315', '1529326315');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-dw-hz/*', '2', null, null, null, '1529066873', '1529066873');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-dw-hz/index', '2', null, null, null, '1529066873', '1529066873');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-dw-hz/view', '2', null, null, null, '1529066873', '1529066873');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-dws-hz-nums/echarts', '2', null, null, null, '1531279370', '1531279370');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-kj-data-ds/index', '2', null, null, null, '1533808614', '1533808614');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-kj-data-ds/view', '2', null, null, null, '1533808614', '1533808614');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-kj-data/*', '2', null, null, null, '1528970929', '1528970929');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-kj-data/index', '2', null, null, null, '1528970924', '1528970924');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-kj-data/index-org', '2', null, null, null, '1531547472', '1531547472');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-kj-data/view', '2', null, null, null, '1528970924', '1528970924');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-kj-data3num/index', '2', null, null, null, '1534930530', '1534930530');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-kj-data3num/view', '2', null, null, null, '1534930530', '1534930530');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-sd-hz-yl/index', '2', null, null, null, '1553396429', '1553396429');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-sd-hz-yl/view', '2', null, null, null, '1553396429', '1553396429');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc-static-yl/index', '2', null, null, null, '1558693797', '1558693797');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc2nums-yl/index', '2', null, null, null, '1556028166', '1556028166');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc3num-yl/index', '2', null, null, null, '1535012020', '1535012020');
INSERT INTO `lt_auth_item` VALUES ('/forum/ssc3num-yl/view', '2', null, null, null, '1535012020', '1535012020');
INSERT INTO `lt_auth_item` VALUES ('/forum/static-hz-profits-perdate/index', '2', null, null, null, '1554737871', '1554737871');
INSERT INTO `lt_auth_item` VALUES ('/forum/static-hz-profits/index', '2', null, null, null, '1554737871', '1554737871');
INSERT INTO `lt_auth_item` VALUES ('/forum/static-per-hz-perdate-profits/index', '2', null, null, null, '1556335566', '1556335566');
INSERT INTO `lt_auth_item` VALUES ('/forum/static3num-arise-perdate/index', '2', null, null, null, '1556028206', '1556028206');
INSERT INTO `lt_auth_item` VALUES ('/forum/static4d-profits-perdate/index', '2', null, null, null, '1551186167', '1551186167');
INSERT INTO `lt_auth_item` VALUES ('/forum/static4d-profits/index', '2', null, null, null, '1551186167', '1551186167');
INSERT INTO `lt_auth_item` VALUES ('/forum/tz-systems-auth/create', '2', null, null, null, '1555144166', '1555144166');
INSERT INTO `lt_auth_item` VALUES ('/forum/tz-systems-auth/delete', '2', null, null, null, '1555144166', '1555144166');
INSERT INTO `lt_auth_item` VALUES ('/forum/tz-systems-auth/index', '2', null, null, null, '1555144166', '1555144166');
INSERT INTO `lt_auth_item` VALUES ('/forum/tz-systems-auth/update', '2', null, null, null, '1555144166', '1555144166');
INSERT INTO `lt_auth_item` VALUES ('/forum/tz-systems-auth/view', '2', null, null, null, '1555144166', '1555144166');
INSERT INTO `lt_auth_item` VALUES ('/forum/tz-systems-users/update', '2', null, null, null, '1557852273', '1557852273');
INSERT INTO `lt_auth_item` VALUES ('/forum/user-custom-plans/*', '2', null, null, null, '1530775672', '1530775672');
INSERT INTO `lt_auth_item` VALUES ('/forum/user-custom-plans/create', '2', null, null, null, '1530771708', '1530771708');
INSERT INTO `lt_auth_item` VALUES ('/forum/user-custom-plans/delete', '2', null, null, null, '1530772443', '1530772443');
INSERT INTO `lt_auth_item` VALUES ('/forum/user-custom-plans/index', '2', null, null, null, '1530771708', '1530771708');
INSERT INTO `lt_auth_item` VALUES ('/forum/user-custom-plans/update', '2', null, null, null, '1530771709', '1530771709');
INSERT INTO `lt_auth_item` VALUES ('/forum/user-custom-plans/view', '2', null, null, null, '1530771708', '1530771708');
INSERT INTO `lt_auth_item` VALUES ('/forum/user-follow-data/*', '2', null, null, null, '1528779642', '1528779642');
INSERT INTO `lt_auth_item` VALUES ('/forum/user-follow-data/create', '2', null, null, null, '1528779642', '1528779642');
INSERT INTO `lt_auth_item` VALUES ('/forum/user-follow-data/delete', '2', null, null, null, '1528779642', '1528779642');
INSERT INTO `lt_auth_item` VALUES ('/forum/user-follow-data/index', '2', null, null, null, '1528773329', '1528773329');
INSERT INTO `lt_auth_item` VALUES ('/forum/user-follow-data/update', '2', null, null, null, '1528779642', '1528779642');
INSERT INTO `lt_auth_item` VALUES ('/forum/user-follow-data/update-status', '2', null, null, null, '1529593705', '1529593705');
INSERT INTO `lt_auth_item` VALUES ('/forum/user-follow-data/view', '2', null, null, null, '1528779642', '1528779642');
INSERT INTO `lt_auth_item` VALUES ('/forum/user-sys-plans/*', '2', null, null, null, '1548520746', '1548520746');
INSERT INTO `lt_auth_item` VALUES ('/forum/user-sys-plans/create', '2', null, null, null, '1548520921', '1548520921');
INSERT INTO `lt_auth_item` VALUES ('/forum/user-sys-plans/delete', '2', null, null, null, '1548520921', '1548520921');
INSERT INTO `lt_auth_item` VALUES ('/forum/user-sys-plans/index', '2', null, null, null, '1548520921', '1548520921');
INSERT INTO `lt_auth_item` VALUES ('/forum/user-sys-plans/switch-buy-type', '2', null, null, null, '1548907193', '1548907193');
INSERT INTO `lt_auth_item` VALUES ('/forum/user-sys-plans/switch-status', '2', null, null, null, '1556617750', '1556617750');
INSERT INTO `lt_auth_item` VALUES ('/forum/user-sys-plans/tz-now', '2', null, null, null, '1556617712', '1556617712');
INSERT INTO `lt_auth_item` VALUES ('/forum/user-sys-plans/update', '2', null, null, null, '1548520921', '1548520921');
INSERT INTO `lt_auth_item` VALUES ('/forum/user-sys-plans/view', '2', null, null, null, '1548520921', '1548520921');
INSERT INTO `lt_auth_item` VALUES ('/forum/user/*', '2', null, null, null, '1529571398', '1529571398');
INSERT INTO `lt_auth_item` VALUES ('/forum/user/index', '2', null, null, null, '1529572477', '1529572477');
INSERT INTO `lt_auth_item` VALUES ('/forum/user/open-systems', '2', null, null, null, '1555291919', '1555291919');
INSERT INTO `lt_auth_item` VALUES ('/forum/user/set-cookie', '2', null, null, null, '1529565358', '1529565358');
INSERT INTO `lt_auth_item` VALUES ('/forum/user/syn-balance', '2', null, null, null, '1529579666', '1529579666');
INSERT INTO `lt_auth_item` VALUES ('/forum/user/sync-one-balance', '2', null, null, null, '1552463812', '1552463812');
INSERT INTO `lt_auth_item` VALUES ('/forum/user/update', '2', null, null, null, '1558769397', '1558769397');
INSERT INTO `lt_auth_item` VALUES ('/forum/user/view', '2', null, null, null, '1529572477', '1529572477');
INSERT INTO `lt_auth_item` VALUES ('/forum/wx-friends/*', '2', null, null, null, '1549376495', '1549376495');
INSERT INTO `lt_auth_item` VALUES ('/forum/wx-friends/create', '2', null, null, null, '1549376586', '1549376586');
INSERT INTO `lt_auth_item` VALUES ('/forum/wx-friends/delete', '2', null, null, null, '1549376586', '1549376586');
INSERT INTO `lt_auth_item` VALUES ('/forum/wx-friends/error', '2', null, null, null, '1549376586', '1549376586');
INSERT INTO `lt_auth_item` VALUES ('/forum/wx-friends/index', '2', null, null, null, '1549376586', '1549376586');
INSERT INTO `lt_auth_item` VALUES ('/forum/wx-friends/login', '2', null, null, null, '1549379748', '1549379748');
INSERT INTO `lt_auth_item` VALUES ('/forum/wx-friends/sync-friends', '2', null, null, null, '1549380083', '1549380083');
INSERT INTO `lt_auth_item` VALUES ('/forum/wx-friends/update', '2', null, null, null, '1549376586', '1549376586');
INSERT INTO `lt_auth_item` VALUES ('/forum/wx-friends/view', '2', null, null, null, '1549376586', '1549376586');
INSERT INTO `lt_auth_item` VALUES ('/forum/wx-msg-status/*', '2', null, null, null, '1549360368', '1549360368');
INSERT INTO `lt_auth_item` VALUES ('/forum/wx-msg-status/create', '2', null, null, null, '1549377138', '1549377138');
INSERT INTO `lt_auth_item` VALUES ('/forum/wx-msg-status/delete', '2', null, null, null, '1549377138', '1549377138');
INSERT INTO `lt_auth_item` VALUES ('/forum/wx-msg-status/error', '2', null, null, null, '1549377138', '1549377138');
INSERT INTO `lt_auth_item` VALUES ('/forum/wx-msg-status/index', '2', null, null, null, '1549377138', '1549377138');
INSERT INTO `lt_auth_item` VALUES ('/forum/wx-msg-status/update', '2', null, null, null, '1549377138', '1549377138');
INSERT INTO `lt_auth_item` VALUES ('/forum/wx-msg-status/view', '2', null, null, null, '1549377138', '1549377138');
INSERT INTO `lt_auth_item` VALUES ('/forum/wx-msg-types/*', '2', null, null, null, '1549426358', '1549426358');
INSERT INTO `lt_auth_item` VALUES ('/forum/wx-msg-types/create', '2', null, null, null, '1549426358', '1549426358');
INSERT INTO `lt_auth_item` VALUES ('/forum/wx-msg-types/delete', '2', null, null, null, '1549426358', '1549426358');
INSERT INTO `lt_auth_item` VALUES ('/forum/wx-msg-types/error', '2', null, null, null, '1549426358', '1549426358');
INSERT INTO `lt_auth_item` VALUES ('/forum/wx-msg-types/index', '2', null, null, null, '1549426358', '1549426358');
INSERT INTO `lt_auth_item` VALUES ('/forum/wx-msg-types/update', '2', null, null, null, '1549426358', '1549426358');
INSERT INTO `lt_auth_item` VALUES ('/forum/wx-msg-types/view', '2', null, null, null, '1549426358', '1549426358');
INSERT INTO `lt_auth_item` VALUES ('/gii/*', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/gii/default/*', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/gii/default/action', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/gii/default/diff', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/gii/default/index', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/gii/default/preview', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/gii/default/view', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/site/*', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/site/error', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/site/index', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/site/login', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/site/logout', '2', null, null, null, '1457330826', '1457330826');
INSERT INTO `lt_auth_item` VALUES ('/test/*', '2', null, null, null, '1528965065', '1528965065');
INSERT INTO `lt_auth_item` VALUES ('/wx/*', '2', null, null, null, '1549361115', '1549361115');
INSERT INTO `lt_auth_item` VALUES ('Admin', '1', 'Administrators', null, null, '1457084487', '1457947508');
INSERT INTO `lt_auth_item` VALUES ('A数据统计', '2', null, null, null, '1457331368', '1457331368');
INSERT INTO `lt_auth_item` VALUES ('member', '1', '普通会员', null, null, '1457084487', '1558425935');
INSERT INTO `lt_auth_item` VALUES ('修改用户', '2', null, null, null, '1457522051', '1457522051');
INSERT INTO `lt_auth_item` VALUES ('修改菜单', '2', null, null, null, '1457330464', '1457405433');
INSERT INTO `lt_auth_item` VALUES ('删除权限', '2', null, null, null, '1457331320', '1457331320');
INSERT INTO `lt_auth_item` VALUES ('删除菜单', '2', null, null, null, '1457330485', '1457330485');
INSERT INTO `lt_auth_item` VALUES ('删除规则', '2', null, null, null, '1457331677', '1457331677');
INSERT INTO `lt_auth_item` VALUES ('删除角色', '2', null, null, null, '1457331161', '1457331161');
INSERT INTO `lt_auth_item` VALUES ('删除路由', '2', null, null, null, '1457331499', '1457331499');
INSERT INTO `lt_auth_item` VALUES ('利润统计', '1', '利润统计', null, null, '1457084487', '1558427620');
INSERT INTO `lt_auth_item` VALUES ('定制化投注', '2', null, null, null, '1457333742', '1457333742');
INSERT INTO `lt_auth_item` VALUES ('投注功能', '1', '投注功能', null, null, '1457084487', '1558427620');
INSERT INTO `lt_auth_item` VALUES ('操作日志', '2', null, null, null, '1468288713', '1468288713');
INSERT INTO `lt_auth_item` VALUES ('收费会员', '1', '收费会员', null, null, '1457084487', '1558426307');
INSERT INTO `lt_auth_item` VALUES ('新增权限', '2', null, null, null, '1457331279', '1457331279');
INSERT INTO `lt_auth_item` VALUES ('新增用户', '2', null, null, null, '1457433802', '1457433802');
INSERT INTO `lt_auth_item` VALUES ('新增菜单', '2', null, null, null, '1457330445', '1457330445');
INSERT INTO `lt_auth_item` VALUES ('新增规则', '2', null, null, null, '1457331552', '1457331610');
INSERT INTO `lt_auth_item` VALUES ('新增角色', '2', null, null, null, '1457331075', '1457331075');
INSERT INTO `lt_auth_item` VALUES ('新增路由', '2', null, null, null, '1457331386', '1457331386');
INSERT INTO `lt_auth_item` VALUES ('更新权限', '2', null, null, null, '1457331303', '1457331303');
INSERT INTO `lt_auth_item` VALUES ('更新规则', '2', null, null, null, '1457331647', '1457331647');
INSERT INTO `lt_auth_item` VALUES ('更新角色', '2', null, null, null, '1457331126', '1457331126');
INSERT INTO `lt_auth_item` VALUES ('更新路由', '2', null, null, null, '1457331492', '1457331492');
INSERT INTO `lt_auth_item` VALUES ('权限分配', '2', null, null, null, '1457418746', '1457418746');
INSERT INTO `lt_auth_item` VALUES ('权限管理', '2', null, null, null, '1457331258', '1457331258');
INSERT INTO `lt_auth_item` VALUES ('查看操作日志', '2', null, null, null, '1468294314', '1468294314');
INSERT INTO `lt_auth_item` VALUES ('查看权限', '2', null, null, null, '1457331342', '1457331342');
INSERT INTO `lt_auth_item` VALUES ('查看用户权限', '2', null, null, null, '1457331965', '1457331965');
INSERT INTO `lt_auth_item` VALUES ('查看菜单', '2', null, null, null, '1457330619', '1457330619');
INSERT INTO `lt_auth_item` VALUES ('查看规则', '2', null, null, null, '1457331692', '1457331692');
INSERT INTO `lt_auth_item` VALUES ('查看角色', '2', null, null, null, '1457331191', '1457331191');
INSERT INTO `lt_auth_item` VALUES ('游戏管理', '2', null, null, null, '1457947508', '1457947508');
INSERT INTO `lt_auth_item` VALUES ('用户权限分配', '2', null, null, null, '1457333258', '1457333258');
INSERT INTO `lt_auth_item` VALUES ('用户管理', '2', null, null, null, '1457079781', '1457331877');
INSERT INTO `lt_auth_item` VALUES ('菜单管理', '2', null, null, null, '1457324314', '1457324314');
INSERT INTO `lt_auth_item` VALUES ('规则管理', '2', null, null, null, '1457331529', '1457331529');
INSERT INTO `lt_auth_item` VALUES ('角色权限分配', '2', null, null, null, '1457333688', '1457333688');
INSERT INTO `lt_auth_item` VALUES ('角色管理', '2', null, null, null, '1457330790', '1457330790');
INSERT INTO `lt_auth_item` VALUES ('调试权限', '2', null, null, null, '1457331368', '1457331368');
INSERT INTO `lt_auth_item` VALUES ('路由分配', '2', null, null, null, '1457333742', '1457333742');
INSERT INTO `lt_auth_item` VALUES ('路由管理', '2', null, null, null, '1457331368', '1457331368');
INSERT INTO `lt_auth_item` VALUES ('遗漏统计', '1', '遗漏统计', null, null, '1457084487', '1558427620');

-- ----------------------------
-- Table structure for `lt_auth_item_child`
-- ----------------------------
DROP TABLE IF EXISTS `lt_auth_item_child`;
CREATE TABLE `lt_auth_item_child` (
  `parent` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `child` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`parent`,`child`),
  KEY `child` (`child`),
  CONSTRAINT `lt_auth_item_child_ibfk_1` FOREIGN KEY (`parent`) REFERENCES `lt_auth_item` (`name`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `lt_auth_item_child_ibfk_2` FOREIGN KEY (`child`) REFERENCES `lt_auth_item` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of lt_auth_item_child
-- ----------------------------
INSERT INTO `lt_auth_item_child` VALUES ('用户权限分配', '/admin/assignment/assign');
INSERT INTO `lt_auth_item_child` VALUES ('新增用户', '/admin/assignment/create');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/admin/assignment/delete');
INSERT INTO `lt_auth_item_child` VALUES ('用户管理', '/admin/assignment/index');
INSERT INTO `lt_auth_item_child` VALUES ('查看用户权限', '/admin/assignment/search');
INSERT INTO `lt_auth_item_child` VALUES ('修改用户', '/admin/assignment/update');
INSERT INTO `lt_auth_item_child` VALUES ('查看用户权限', '/admin/assignment/view');
INSERT INTO `lt_auth_item_child` VALUES ('操作日志', '/admin/log/index');
INSERT INTO `lt_auth_item_child` VALUES ('查看操作日志', '/admin/log/view');
INSERT INTO `lt_auth_item_child` VALUES ('新增菜单', '/admin/menu/create');
INSERT INTO `lt_auth_item_child` VALUES ('删除菜单', '/admin/menu/delete');
INSERT INTO `lt_auth_item_child` VALUES ('菜单管理', '/admin/menu/index');
INSERT INTO `lt_auth_item_child` VALUES ('修改菜单', '/admin/menu/update');
INSERT INTO `lt_auth_item_child` VALUES ('查看菜单', '/admin/menu/view');
INSERT INTO `lt_auth_item_child` VALUES ('权限分配', '/admin/permission/assign');
INSERT INTO `lt_auth_item_child` VALUES ('新增权限', '/admin/permission/create');
INSERT INTO `lt_auth_item_child` VALUES ('删除权限', '/admin/permission/delete');
INSERT INTO `lt_auth_item_child` VALUES ('权限管理', '/admin/permission/index');
INSERT INTO `lt_auth_item_child` VALUES ('查看权限', '/admin/permission/search');
INSERT INTO `lt_auth_item_child` VALUES ('更新权限', '/admin/permission/update');
INSERT INTO `lt_auth_item_child` VALUES ('查看权限', '/admin/permission/view');
INSERT INTO `lt_auth_item_child` VALUES ('角色权限分配', '/admin/role/assign');
INSERT INTO `lt_auth_item_child` VALUES ('新增角色', '/admin/role/create');
INSERT INTO `lt_auth_item_child` VALUES ('删除角色', '/admin/role/delete');
INSERT INTO `lt_auth_item_child` VALUES ('角色管理', '/admin/role/index');
INSERT INTO `lt_auth_item_child` VALUES ('查看角色', '/admin/role/search');
INSERT INTO `lt_auth_item_child` VALUES ('更新角色', '/admin/role/update');
INSERT INTO `lt_auth_item_child` VALUES ('查看角色', '/admin/role/view');
INSERT INTO `lt_auth_item_child` VALUES ('路由分配', '/admin/route/assign');
INSERT INTO `lt_auth_item_child` VALUES ('新增路由', '/admin/route/create');
INSERT INTO `lt_auth_item_child` VALUES ('查看规则', '/admin/route/index');
INSERT INTO `lt_auth_item_child` VALUES ('查看规则', '/admin/route/search');
INSERT INTO `lt_auth_item_child` VALUES ('新增规则', '/admin/rule/create');
INSERT INTO `lt_auth_item_child` VALUES ('删除规则', '/admin/rule/delete');
INSERT INTO `lt_auth_item_child` VALUES ('规则管理', '/admin/rule/index');
INSERT INTO `lt_auth_item_child` VALUES ('路由管理', '/admin/rule/index');
INSERT INTO `lt_auth_item_child` VALUES ('更新规则', '/admin/rule/update');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/betting-records/*');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/betting-records/cancel-order');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '/forum/betting-records/cancel-order');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/betting-records/index');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '/forum/betting-records/index');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/betting-records/pre-date-profits');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/betting-records/pre-date-profits');
INSERT INTO `lt_auth_item_child` VALUES ('利润统计', '/forum/betting-records/pre-date-profits');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/betting-records/reverse-tz-now');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/betting-records/sys-tz-list');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/betting-records/tz-now');
INSERT INTO `lt_auth_item_child` VALUES ('投注功能', '/forum/betting-records/tz-now');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/index/get-balance');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/index/index');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/index/syn-balance');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/index/test');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/playways-auth/create');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/playways-auth/delete');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/playways-auth/index');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/playways-auth/update');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/playways-auth/view');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/playways/create');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/playways/delete');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/playways/index');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/playways/update');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/playways/view');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/ssc-ds-static/view');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/ssc-ds-yl/index');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '/forum/ssc-ds-yl/index');
INSERT INTO `lt_auth_item_child` VALUES ('遗漏统计', '/forum/ssc-ds-yl/index');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/ssc-ds-yl/view');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/ssc-dw-hz-static/*');
INSERT INTO `lt_auth_item_child` VALUES ('游戏管理', '/forum/ssc-dw-hz-static/*');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/ssc-dw-hz-static/echarts');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/ssc-dw-hz-static/index');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/ssc-dw-hz-static/view');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/ssc-dw-hz-yl/*');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/ssc-dw-hz-yl/*');
INSERT INTO `lt_auth_item_child` VALUES ('游戏管理', '/forum/ssc-dw-hz-yl/*');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/ssc-dw-hz-yl/index');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/ssc-dw-hz-yl/index');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '/forum/ssc-dw-hz-yl/index');
INSERT INTO `lt_auth_item_child` VALUES ('遗漏统计', '/forum/ssc-dw-hz-yl/index');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/ssc-dw-hz-yl/view');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/ssc-dw-hz-yl/view');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/ssc-dw-hz/*');
INSERT INTO `lt_auth_item_child` VALUES ('游戏管理', '/forum/ssc-dw-hz/*');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/ssc-dw-hz/index');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '/forum/ssc-dw-hz/index');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/ssc-dw-hz/view');
INSERT INTO `lt_auth_item_child` VALUES ('定制化投注', '/forum/ssc-dws-hz-nums/echarts');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/ssc-kj-data-ds/index');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '/forum/ssc-kj-data-ds/index');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/ssc-kj-data-ds/view');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/ssc-kj-data/*');
INSERT INTO `lt_auth_item_child` VALUES ('游戏管理', '/forum/ssc-kj-data/*');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/ssc-kj-data/index');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '/forum/ssc-kj-data/index');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/ssc-kj-data/view');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/ssc-kj-data3num/index');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '/forum/ssc-kj-data3num/index');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/ssc-kj-data3num/view');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/ssc-sd-hz-yl/index');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '/forum/ssc-sd-hz-yl/index');
INSERT INTO `lt_auth_item_child` VALUES ('遗漏统计', '/forum/ssc-sd-hz-yl/index');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/ssc-sd-hz-yl/view');
INSERT INTO `lt_auth_item_child` VALUES ('遗漏统计', '/forum/ssc-static-yl/index');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/ssc2nums-yl/index');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '/forum/ssc2nums-yl/index');
INSERT INTO `lt_auth_item_child` VALUES ('遗漏统计', '/forum/ssc2nums-yl/index');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/ssc3num-yl/index');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '/forum/ssc3num-yl/index');
INSERT INTO `lt_auth_item_child` VALUES ('遗漏统计', '/forum/ssc3num-yl/index');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/ssc3num-yl/view');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/static-hz-profits-perdate/index');
INSERT INTO `lt_auth_item_child` VALUES ('利润统计', '/forum/static-hz-profits-perdate/index');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/static-hz-profits/index');
INSERT INTO `lt_auth_item_child` VALUES ('利润统计', '/forum/static-hz-profits/index');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/static-per-hz-perdate-profits/index');
INSERT INTO `lt_auth_item_child` VALUES ('利润统计', '/forum/static-per-hz-perdate-profits/index');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/static3num-arise-perdate/index');
INSERT INTO `lt_auth_item_child` VALUES ('利润统计', '/forum/static3num-arise-perdate/index');
INSERT INTO `lt_auth_item_child` VALUES ('遗漏统计', '/forum/static3num-arise-perdate/index');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/static4d-profits-perdate/index');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/static4d-profits-perdate/index');
INSERT INTO `lt_auth_item_child` VALUES ('利润统计', '/forum/static4d-profits-perdate/index');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/static4d-profits/index');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/static4d-profits/index');
INSERT INTO `lt_auth_item_child` VALUES ('利润统计', '/forum/static4d-profits/index');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/tz-systems-auth/create');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/tz-systems-auth/delete');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/tz-systems-auth/index');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/tz-systems-auth/update');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/tz-systems-auth/view');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '/forum/tz-systems-users/update');
INSERT INTO `lt_auth_item_child` VALUES ('定制化投注', '/forum/user-custom-plans/*');
INSERT INTO `lt_auth_item_child` VALUES ('定制化投注', '/forum/user-custom-plans/create');
INSERT INTO `lt_auth_item_child` VALUES ('定制化投注', '/forum/user-custom-plans/delete');
INSERT INTO `lt_auth_item_child` VALUES ('定制化投注', '/forum/user-custom-plans/index');
INSERT INTO `lt_auth_item_child` VALUES ('定制化投注', '/forum/user-custom-plans/update');
INSERT INTO `lt_auth_item_child` VALUES ('定制化投注', '/forum/user-custom-plans/view');
INSERT INTO `lt_auth_item_child` VALUES ('游戏管理', '/forum/user-follow-data/*');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/user-follow-data/update-status');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/user-sys-plans/*');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/user-sys-plans/create');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '/forum/user-sys-plans/create');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/user-sys-plans/delete');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '/forum/user-sys-plans/delete');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/user-sys-plans/index');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '/forum/user-sys-plans/index');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/user-sys-plans/switch-buy-type');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '/forum/user-sys-plans/switch-status');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '/forum/user-sys-plans/tz-now');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/user-sys-plans/update');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '/forum/user-sys-plans/update');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/user-sys-plans/view');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '/forum/user-sys-plans/view');
INSERT INTO `lt_auth_item_child` VALUES ('用户管理', '/forum/user/index');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/user/open-systems');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/user/set-cookie');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/user/syn-balance');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/user/sync-one-balance');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '/forum/user/sync-one-balance');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '/forum/user/update');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/user/view');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '/forum/user/view');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/wx-friends/*');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/wx-friends/create');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/wx-friends/delete');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/wx-friends/error');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/wx-friends/index');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/wx-friends/login');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/wx-friends/sync-friends');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/wx-friends/update');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/wx-friends/view');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/wx-msg-status/*');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/wx-msg-status/create');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/wx-msg-status/delete');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/wx-msg-status/error');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/wx-msg-status/index');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/wx-msg-status/update');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/wx-msg-status/view');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/wx-msg-types/*');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/wx-msg-types/create');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/wx-msg-types/delete');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/wx-msg-types/error');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/wx-msg-types/index');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/wx-msg-types/update');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/forum/wx-msg-types/view');
INSERT INTO `lt_auth_item_child` VALUES ('调试权限', '/test/*');
INSERT INTO `lt_auth_item_child` VALUES ('member', '/wx/*');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', 'A数据统计');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '修改用户');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '修改菜单');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '删除权限');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '删除菜单');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '删除规则');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '删除角色');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '删除路由');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '利润统计');
INSERT INTO `lt_auth_item_child` VALUES ('member', '投注功能');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '投注功能');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '操作日志');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '新增权限');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '新增用户');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '新增菜单');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '新增规则');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '新增角色');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '新增路由');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '更新权限');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '更新规则');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '更新角色');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '更新路由');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '权限分配');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '权限管理');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '查看操作日志');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '查看权限');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '查看用户权限');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '查看菜单');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '查看规则');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '查看角色');
INSERT INTO `lt_auth_item_child` VALUES ('member', '游戏管理');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '用户权限分配');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '用户管理');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '菜单管理');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '规则管理');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '角色权限分配');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '角色管理');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '调试权限');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '路由分配');
INSERT INTO `lt_auth_item_child` VALUES ('Admin', '路由管理');
INSERT INTO `lt_auth_item_child` VALUES ('member', '遗漏统计');
INSERT INTO `lt_auth_item_child` VALUES ('收费会员', '遗漏统计');

-- ----------------------------
-- Table structure for `lt_auth_rule`
-- ----------------------------
DROP TABLE IF EXISTS `lt_auth_rule`;
CREATE TABLE `lt_auth_rule` (
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `data` blob,
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of lt_auth_rule
-- ----------------------------
INSERT INTO `lt_auth_rule` VALUES ('member', null, '1457084487', '1457947508');
INSERT INTO `lt_auth_rule` VALUES ('利润统计', null, '1457084487', '1457084487');
INSERT INTO `lt_auth_rule` VALUES ('投注功能', null, '1457084487', '1457084487');
INSERT INTO `lt_auth_rule` VALUES ('收费会员', null, '1457084487', '1457084487');
INSERT INTO `lt_auth_rule` VALUES ('遗漏统计', null, '1457084487', '1457084487');

-- ----------------------------
-- Table structure for `lt_betting_records`
-- ----------------------------
DROP TABLE IF EXISTS `lt_betting_records`;
CREATE TABLE `lt_betting_records` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `codes` text COMMENT '投注号码',
  `uid` int(11) DEFAULT NULL COMMENT '用户id',
  `account` varchar(255) DEFAULT NULL,
  `playway` tinyint(4) DEFAULT '10' COMMENT '投注方式：10定位胆',
  `tz_type` tinyint(3) DEFAULT '0' COMMENT '投注类型',
  `playway_name` varchar(32) DEFAULT NULL COMMENT '投注方式',
  `betting_money` decimal(11,2) DEFAULT '0.00' COMMENT '投注金额',
  `bonus` decimal(11,2) DEFAULT '0.00' COMMENT '中奖金额',
  `single` float DEFAULT NULL COMMENT '倍数(元)',
  `profits` decimal(11,2) DEFAULT NULL COMMENT '利润',
  `qihao` varchar(20) DEFAULT NULL COMMENT '期号',
  `kj_codes` varchar(24) DEFAULT NULL COMMENT '开奖号码',
  `position` varchar(128) DEFAULT NULL COMMENT '定位位置',
  `status` tinyint(4) DEFAULT '0' COMMENT '中奖状态：0:正常、1:中奖、2:未中奖',
  `cancel_status` tinyint(4) DEFAULT '0' COMMENT '撤单状态：0未撤单1已撤单',
  `sn` varchar(255) DEFAULT NULL COMMENT '方案号',
  `snid` text COMMENT '订单号',
  `plan_id` int(11) DEFAULT NULL COMMENT '计划id',
  `buy_type` tinyint(1) DEFAULT '1' COMMENT '购买方向:0反买1正买',
  `is_simulate` tinyint(4) DEFAULT '1' COMMENT '是否模拟投注',
  `order_type` tinyint(4) DEFAULT '1' COMMENT '订单来源：1跟投订单 2大数据订单 3系统计划订单',
  `tz_system_id` int(11) DEFAULT NULL COMMENT '投注系统tz_systems.id',
  `lotteryclass` varchar(10) DEFAULT 'ssc' COMMENT '彩种',
  `lottery_type` tinyint(1) DEFAULT '5' COMMENT '彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc',
  `createtime` int(11) DEFAULT NULL,
  `create_time` varchar(32) DEFAULT NULL COMMENT '投注时间',
  `updated_at` int(11) DEFAULT NULL COMMENT '更新时间',
  `created_at` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `account` (`account`) USING BTREE,
  KEY `sn` (`sn`),
  KEY `qihao` (`qihao`),
  KEY `tz_money` (`betting_money`),
  KEY `playway` (`playway`),
  KEY `profits` (`profits`),
  KEY `position` (`position`),
  KEY `is_simulate` (`is_simulate`),
  KEY `uid` (`uid`),
  FULLTEXT KEY `codes` (`codes`),
  FULLTEXT KEY `snid` (`snid`)
) ENGINE=MyISAM AUTO_INCREMENT=8315 DEFAULT CHARSET=utf8 COMMENT='用户投注记录表';

-- ----------------------------
-- Records of lt_betting_records
-- ----------------------------
INSERT INTO `lt_betting_records` VALUES ('7049', '3,9,9,9@4,8,9,9@4,9,8,9@4,9,9,8@5,7,9,9@5,8,8,9@5,8,9,8@5,9,7,9@5,9,8,8@5,9,9,7@6,6,9,9@6,7,8,9@6,7,9,8@6,8,7,9@6,8,8,8@6,8,9,7@6,9,6,9@6,9,7,8@6,9,8,7@6,9,9,6@7,5,9,9@7,6,8,9@7,6,9,8@7,7,7,9@7,7,8,8@7,7,9,7@7,8,6,9@7,8,7,8@7,8,8,7@7,8,9,6@7,9,5,9@7,9,6,8@7,9,7,7@7,9,8,6@7,9,9,5@8,4,9,9@8,5,8,9@8,5,9,8@8,6,7,9@8,6,8,8@8,6,9,7@8,7,6,9@8,7,7,8@8,7,8,7@8,7,9,6@8,8,5,9@8,8,6,8@8,8,7,7@8,8,8,6@8,8,9,5@8,9,4,9@8,9,5,8@8,9,6,7@8,9,7,6@8,9,8,5@8,9,9,4@9,3,9,9@9,4,8,9@9,4,9,8@9,5,7,9@9,5,8,8@9,5,9,7@9,6,6,9@9,6,7,8@9,6,8,7@9,6,9,6@9,7,5,9@9,7,6,8@9,7,7,7@9,7,8,6@9,7,9,5@9,8,4,9@9,8,5,8@9,8,6,7@9,8,7,6@9,8,8,5@9,8,9,4@9,9,3,9@9,9,4,8@9,9,5,7@9,9,6,6@9,9,7,5@9,9,8,4@9,9,9,3', '11', 'gaozi2018', '3', '20', '四字定', '126.00', '0.00', '1.5', '-126.00', '190525030', '8,3,5,8,6', null, '1', '0', 'SSC190525135213757391A0', '586109', '11', '1', '0', '3', '2', 'ssc', '5', '1558763528', '2019-05-25 13:52:08', '1558764666', '1558763528');
