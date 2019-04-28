/*
Navicat MySQL Data Transfer

Source Server         : 120.77.157.40
Source Server Version : 50641
Source Host           : 120.77.157.40:3306
Source Database       : lottery

Target Server Type    : MYSQL
Target Server Version : 50641
File Encoding         : 65001

Date: 2018-12-22 12:54:50
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `lt_sys_plans_codes`
-- ----------------------------
DROP TABLE IF EXISTS `lt_sys_plans_codes`;
CREATE TABLE `lt_sys_plans_codes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(640) NOT NULL COMMENT '投注号码',
  `playway` tinyint(4) NOT NULL DEFAULT '10' COMMENT '投注方式:1二字定2三字定3四字定',
  `position` varchar(64) NOT NULL DEFAULT '' COMMENT '定位位置',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型:0计划外1计划内',
  `tz_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '投注类型:1单双三字定2大小三字定3大小单双三字定',
  `created_at` int(11) NOT NULL COMMENT '创建时间',
  `updated_at` int(11) NOT NULL COMMENT '更新时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=32 DEFAULT CHARSET=utf8 COMMENT='系统计划投注号码';

-- ----------------------------
-- Records of lt_sys_plans_codes
-- ----------------------------
INSERT INTO `lt_sys_plans_codes` VALUES ('1', '13579,X,13579,13579', '2', '1,3,4', '1', '3', '1541092572', '1545382881', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('2', '02468,X,13579,13579', '2', '1,3,4', '1', '3', '1541092572', '1545382881', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('3', 'X,02468,13579,02468', '2', '2,3,4', '1', '3', '1541092572', '1545382881', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('4', '02468,X,13579,02468', '2', '1,3,4', '1', '3', '1541092572', '1545382882', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('5', '02468,02468,X,13579', '2', '1,2,4', '0', '3', '1541092572', '1545382882', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('6', '02468,X,02468,13579', '2', '1,3,4', '1', '3', '1541092572', '1545382881', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('7', 'X,13579,02468,02468', '2', '2,3,4', '0', '3', '1541092572', '1545382882', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('8', 'X,13579,02468,13579', '2', '2,3,4', '1', '3', '1541092572', '1545382882', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('9', '13579,02468,X,02468', '2', '1,2,4', '1', '3', '1541092572', '1545382881', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('10', '02468,13579,02468,X', '2', '1,2,3', '0', '3', '1541092572', '1545382882', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('11', '02468,02468,X,02468', '2', '1,2,4', '1', '3', '1541092572', '1545382881', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('12', '13579,X,13579,02468', '2', '1,3,4', '1', '3', '1541092572', '1545382882', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('13', '02468,X,02468,02468', '2', '1,3,4', '0', '3', '1541092572', '1545382882', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('14', '13579,02468,13579,X', '2', '1,2,3', '1', '3', '1541092572', '1545382882', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('15', '13579,X,02468,02468', '2', '1,3,4', '0', '3', '1541092572', '1545382882', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('16', '02468,13579,X,13579', '2', '1,2,4', '1', '3', '1541092572', '1545382881', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('17', 'X,13579,13579,02468', '2', '2,3,4', '1', '3', '1541092572', '1545382882', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('18', '13579,02468,02468,X', '2', '1,2,3', '0', '3', '1541092572', '1545382881', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('19', 'X,02468,13579,13579', '2', '2,3,4', '0', '3', '1541092572', '1545382881', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('20', '02468,02468,02468,X', '2', '1,2,3', '0', '3', '1541092572', '1545382882', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('21', '13579,X,02468,13579', '2', '1,3,4', '1', '3', '1541092572', '1545382881', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('22', '02468,02468,13579,X', '2', '1,2,3', '1', '3', '1541092572', '1545382881', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('23', '13579,13579,X,02468', '2', '1,2,4', '0', '3', '1541092572', '1545382882', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('24', 'X,02468,02468,02468', '2', '2,3,4', '0', '3', '1541092572', '1545382882', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('25', '13579,02468,X,13579', '2', '1,2,4', '0', '3', '1541092572', '1545382882', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('26', '02468,13579,X,02468', '2', '1,2,4', '1', '3', '1541092572', '1545382881', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('27', 'X,13579,13579,13579', '2', '2,3,4', '1', '3', '1541092572', '1545382882', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('28', '02468,13579,13579,X', '2', '1,2,3', '1', '3', '1541092572', '1545382881', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('29', '13579,13579,02468,X', '2', '1,2,3', '1', '3', '1541092572', '1545382881', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('30', '13579,13579,X,13579', '2', '1,2,4', '1', '3', '1541092572', '1545382881', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('31', '13579,13579,13579,X', '2', '1,2,3', '1', '3', '1541092572', '1545382881', '2018-12-21 17:49:05');
INSERT INTO `lt_sys_plans_codes` VALUES ('32', 'X,02468,02468,13579', '2', '2,3,4', '0', '3', '1541092572', '1545382882', '2018-12-21 17:49:05');
