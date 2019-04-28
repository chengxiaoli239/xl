/*
Navicat MySQL Data Transfer

Source Server         : me（120.77.157.40）
Source Server Version : 50641
Source Host           : 120.77.157.40:3306
Source Database       : lottery

Target Server Type    : MYSQL
Target Server Version : 50641
File Encoding         : 65001

Date: 2018-10-29 14:54:50
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `lt_user_custom_plans`
-- ----------------------------
DROP TABLE IF EXISTS `lt_user_custom_plans`;
CREATE TABLE `lt_user_custom_plans` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `account` varchar(32) DEFAULT NULL COMMENT '用户账号',
  `hezhis` varchar(24) DEFAULT NULL COMMENT '号码和值',
  `playway` tinyint(4) NOT NULL DEFAULT '10' COMMENT '投注方式',
  `positions` varchar(50) DEFAULT '1,3' COMMENT '定位位置',
  `status` tinyint(4) DEFAULT '1' COMMENT '是否激活',
  `codes` text COMMENT '方案号码',
  `playway_type` tinyint(4) DEFAULT '1' COMMENT '投注方式：1:和值 2:单双',
  `single` float(4,1) DEFAULT '1.0' COMMENT '投注倍数(元/注)',
  `periods_open` tinyint(11) DEFAULT '20' COMMENT '开启统计的期数',
  `threshold_open` tinyint(4) DEFAULT NULL COMMENT '开启阈值',
  `periods_close` tinyint(4) DEFAULT '6' COMMENT '关闭统计的期数',
  `threshold_close` tinyint(4) DEFAULT '5' COMMENT '关闭阈值',
  `is_simulate` tinyint(4) DEFAULT '0' COMMENT '是否激活',
  `created_at` int(11) DEFAULT NULL COMMENT '创建时间',
  `updated_at` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `account` (`account`)
) ENGINE=MyISAM AUTO_INCREMENT=33 DEFAULT CHARSET=utf8 COMMENT='用户定制化追号表';

-- ----------------------------
-- Records of lt_user_custom_plans
-- ----------------------------
INSERT INTO `lt_user_custom_plans` VALUES ('1', 'gaozi2017', '111', '2', '1,2,3', '1', '13579,13579,13579,X', '2', '1.0', '20', '0', '6', '10', '1', '1535795778', '1540634552');
INSERT INTO `lt_user_custom_plans` VALUES ('2', 'gaozi2017', '112', '2', '1,2,3', '1', '13579,13579,02468,X', '2', '1.0', '20', '0', '6', '10', '1', '1535795778', '1535795778');
INSERT INTO `lt_user_custom_plans` VALUES ('3', 'gaozi2017', '121', '2', '1,2,3', '1', '13579,02468,13579,X', '2', '1.0', '20', '0', '6', '10', '1', '1535795778', '1535795778');
INSERT INTO `lt_user_custom_plans` VALUES ('4', 'gaozi2017', '211', '2', '1,2,3', '1', '02468,13579,13579,X', '2', '1.0', '20', '0', '6', '10', '1', '1535795778', '1535795778');
INSERT INTO `lt_user_custom_plans` VALUES ('5', 'gaozi2017', '122', '2', '1,2,3', '1', '13579,02468,02468,X', '2', '1.0', '20', '0', '6', '10', '1', '1535795778', '1535795778');
INSERT INTO `lt_user_custom_plans` VALUES ('6', 'gaozi2017', '212', '2', '1,2,3', '1', '02468,13579,02468,X', '2', '1.0', '20', '0', '6', '10', '1', '1535795778', '1535795778');
INSERT INTO `lt_user_custom_plans` VALUES ('7', 'gaozi2017', '221', '2', '1,2,3', '1', '02468,02468,13579,X', '2', '1.0', '20', '0', '6', '10', '1', '1535795778', '1535795778');
INSERT INTO `lt_user_custom_plans` VALUES ('8', 'gaozi2017', '222', '2', '1,2,3', '1', '02468,02468,02468,X', '2', '1.0', '20', '0', '6', '10', '1', '1535795778', '1535795778');
INSERT INTO `lt_user_custom_plans` VALUES ('9', 'gaozi2017', '111', '2', '1,2,4', '1', '13579,13579,X,13579', '2', '1.0', '20', '0', '6', '10', '1', '1535795778', '1535795778');
INSERT INTO `lt_user_custom_plans` VALUES ('10', 'gaozi2017', '112', '2', '1,2,4', '1', '13579,13579,X,02468', '2', '1.0', '20', '0', '6', '10', '1', '1535795778', '1535795778');
INSERT INTO `lt_user_custom_plans` VALUES ('11', 'gaozi2017', '121', '2', '1,2,4', '1', '13579,02468,X,13579', '2', '1.0', '20', '0', '6', '10', '1', '1535795778', '1535795778');
INSERT INTO `lt_user_custom_plans` VALUES ('12', 'gaozi2017', '211', '2', '1,2,4', '1', '02468,13579,X,13579', '2', '1.0', '20', '0', '6', '10', '1', '1535795778', '1535795778');
INSERT INTO `lt_user_custom_plans` VALUES ('13', 'gaozi2017', '122', '2', '1,2,4', '1', '13579,02468,X,02468', '2', '1.0', '20', '0', '6', '10', '1', '1535795778', '1535795778');
INSERT INTO `lt_user_custom_plans` VALUES ('14', 'gaozi2017', '212', '2', '1,2,4', '1', '02468,13579,X,02468', '2', '1.0', '20', '0', '6', '10', '1', '1535795778', '1535795778');
INSERT INTO `lt_user_custom_plans` VALUES ('15', 'gaozi2017', '221', '2', '1,2,4', '1', '02468,02468,X,13579', '2', '1.0', '20', '0', '6', '10', '1', '1535795778', '1535795778');
INSERT INTO `lt_user_custom_plans` VALUES ('16', 'gaozi2017', '222', '2', '1,2,4', '1', '02468,02468,X,02468', '2', '1.0', '20', '0', '6', '10', '1', '1535795778', '1535795778');
INSERT INTO `lt_user_custom_plans` VALUES ('17', 'gaozi2017', '111', '2', '1,3,4', '1', '13579,X,13579,13579', '2', '1.0', '20', '0', '6', '10', '1', '1535795779', '1535795779');
INSERT INTO `lt_user_custom_plans` VALUES ('18', 'gaozi2017', '112', '2', '1,3,4', '1', '13579,X,13579,02468', '2', '1.0', '20', '0', '6', '10', '1', '1535795779', '1535795779');
INSERT INTO `lt_user_custom_plans` VALUES ('19', 'gaozi2017', '121', '2', '1,3,4', '1', '13579,X,02468,13579', '2', '1.0', '20', '0', '6', '10', '1', '1535795779', '1535795779');
INSERT INTO `lt_user_custom_plans` VALUES ('20', 'gaozi2017', '211', '2', '1,3,4', '1', '02468,X,13579,13579', '2', '1.0', '20', '0', '6', '10', '1', '1535795779', '1535795779');
INSERT INTO `lt_user_custom_plans` VALUES ('21', 'gaozi2017', '122', '2', '1,3,4', '1', '13579,X,02468,02468', '2', '1.0', '20', '0', '6', '10', '1', '1535795779', '1535795779');
INSERT INTO `lt_user_custom_plans` VALUES ('22', 'gaozi2017', '212', '2', '1,3,4', '1', '02468,X,13579,02468', '2', '1.0', '20', '0', '6', '10', '1', '1535795779', '1535795779');
INSERT INTO `lt_user_custom_plans` VALUES ('23', 'gaozi2017', '221', '2', '1,3,4', '1', '02468,X,02468,13579', '2', '1.0', '20', '0', '6', '10', '1', '1535795779', '1535795779');
INSERT INTO `lt_user_custom_plans` VALUES ('24', 'gaozi2017', '222', '2', '1,3,4', '1', '02468,X,02468,02468', '2', '1.0', '20', '0', '6', '10', '1', '1535795779', '1535795779');
INSERT INTO `lt_user_custom_plans` VALUES ('25', 'gaozi2017', '111', '2', '2,3,4', '1', 'X,13579,13579,13579', '2', '1.0', '20', '0', '6', '10', '1', '1535795779', '1535795779');
INSERT INTO `lt_user_custom_plans` VALUES ('26', 'gaozi2017', '112', '2', '2,3,4', '1', 'X,13579,13579,02468', '2', '1.0', '20', '0', '6', '10', '1', '1535795779', '1535795779');
INSERT INTO `lt_user_custom_plans` VALUES ('27', 'gaozi2017', '121', '2', '2,3,4', '1', 'X,13579,02468,13579', '2', '1.0', '20', '0', '6', '10', '1', '1535795779', '1535795779');
INSERT INTO `lt_user_custom_plans` VALUES ('28', 'gaozi2017', '211', '2', '2,3,4', '1', 'X,02468,13579,13579', '2', '1.0', '20', '0', '6', '10', '1', '1535795779', '1535795779');
INSERT INTO `lt_user_custom_plans` VALUES ('29', 'gaozi2017', '122', '2', '2,3,4', '1', 'X,13579,02468,02468', '2', '1.0', '20', '0', '6', '10', '1', '1535795779', '1535795779');
INSERT INTO `lt_user_custom_plans` VALUES ('30', 'gaozi2017', '212', '2', '2,3,4', '1', 'X,02468,13579,02468', '2', '1.0', '20', '0', '6', '10', '1', '1535795779', '1535795779');
INSERT INTO `lt_user_custom_plans` VALUES ('31', 'gaozi2017', '221', '2', '2,3,4', '1', 'X,02468,02468,13579', '2', '1.0', '20', '0', '6', '10', '1', '1535795779', '1535795779');
INSERT INTO `lt_user_custom_plans` VALUES ('32', 'gaozi2017', '222', '2', '2,3,4', '1', 'X,02468,02468,02468', '2', '1.0', '20', '0', '6', '10', '1', '1535795779', '1535795779');
