/*
Navicat MySQL Data Transfer

Source Server         : me（20.77.157.40）
Source Server Version : 50639
Source Host           : 120.77.157.40:3306
Source Database       : lottery

Target Server Type    : MYSQL
Target Server Version : 50639
File Encoding         : 65001

Date: 2018-07-03 09:33:20
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `lt_user_follow_data`
-- ----------------------------
DROP TABLE IF EXISTS `lt_user_follow_data`;
CREATE TABLE `lt_user_follow_data` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `account` varchar(32) DEFAULT NULL COMMENT '用户账号',
  `code` varchar(120) DEFAULT NULL COMMENT '投注号码',
  `codes_hezhi` tinyint(4) DEFAULT NULL COMMENT '号码和值',
  `playway` tinyint(4) NOT NULL DEFAULT '10' COMMENT '投注方式',
  `position` varchar(10) DEFAULT '1,3' COMMENT '定位位置',
  `reference_codes` varchar(255) DEFAULT NULL COMMENT '参考码',
  `is_follow` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否追号',
  `is_simulate` tinyint(4) DEFAULT '1' COMMENT '是否模拟',
  `status` tinyint(4) DEFAULT '1' COMMENT '是否激活',
  `single` float(4,1) DEFAULT '1.0' COMMENT '投注倍数(元/注)',
  `plan_type` tinyint(4) DEFAULT '1' COMMENT '计划类型:1用户2大数据',
  `created_at` int(11) DEFAULT NULL COMMENT '创建时间',
  `updated_at` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `account` (`account`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户追号记录表';

-- ----------------------------
-- Records of lt_user_follow_data
-- ----------------------------
INSERT INTO `lt_user_follow_data` VALUES ('', 'gaozi2017', 'X,0,9,X@X,9,0,X@X,1,8,X@X,8,1,X@X,2,7,X@X,7,2,X@X,3,6,X@X,6,3,X@X,4,5,X@X,5,4,X', '9', '1', '2,3', '', '1', '0', '0', '3.0', '1', '1529741948', '1530542684');
INSERT INTO `lt_user_follow_data` VALUES ('', 'gaozi2017', 'X,0,X,9@X,9,X,0@X,1,X,8@X,8,X,1@X,2,X,7@X,7,X,2@X,3,X,6@X,6,X,3@X,4,X,5@X,5,X,4', '9', '1', '2,4', '', '1', '0', '0', '1.0', '1', '1529657549', '1530541874');
INSERT INTO `lt_user_follow_data` VALUES ('', 'gaozi2017', 'X,X,0,9@X,X,9,0@X,X,1,8@X,X,8,1@X,X,2,7@X,X,7,2@X,X,3,6@X,X,6,3@X,X,4,5@X,X,5,4', '9', '1', '3,4', null, '1', '0', '0', '1.0', '1', '1529753585', '1530545969');
INSERT INTO `lt_user_follow_data` VALUES ('', 'gaozi2017', 'X,0,8,X@X,8,0,X@X,1,7,X@X,7,1,X@X,2,6,X@X,6,2,X@X,3,5,X@X,5,3,X@X,4,4,X', '8', '1', '2,3', null, '1', '0', '0', '2.5', '1', '1529773633', '1530172079');
INSERT INTO `lt_user_follow_data` VALUES ('', 'gaozi2017', 'X,0,X,8@X,8,X,0@X,1,X,7@X,7,X,1@X,2,X,6@X,6,X,2@X,3,X,5@X,5,X,3@X,4,X,4', '8', '1', '2,4', null, '1', '0', '1', '2.0', '1', '1529851463', '1530553136');
INSERT INTO `lt_user_follow_data` VALUES ('', 'gaozi2017', 'X,X,0,8@X,X,8,0@X,X,1,7@X,X,7,1@X,X,2,6@X,X,6,2@X,X,3,5@X,X,5,3@X,X,4,4', '8', '1', '3,4', null, '1', '0', '0', '0.5', '1', '1529901624', '1530172127');
INSERT INTO `lt_user_follow_data` VALUES ('', 'gaozi2017', '0,9,X,X@9,0,X,X@1,8,X,X@8,1,X,X@2,7,X,X@7,2,X,X@3,6,X,X@6,3,X,X@4,5,X,X@5,4,X,X', '9', '1', '1,2', null, '1', '0', '0', '1.0', '1', '1529916828', '1530172142');
INSERT INTO `lt_user_follow_data` VALUES ('', 'gaozi2017', '0,9,X,X@9,0,X,X@1,8,X,X@8,1,X,X@2,7,X,X@7,2,X,X@3,6,X,X@6,3,X,X@4,5,X,X@5,4,X,X', '9', '1', '1,2', null, '1', '0', '0', '1.0', '1', '1529916828', '1530172142');
INSERT INTO `lt_user_follow_data` VALUES ('', 'TedGod', 'X,0,X,8@X,8,X,0@X,1,X,7@X,7,X,1@X,2,X,6@X,6,X,2@X,3,X,5@X,5,X,3@X,4,X,4', '8', '1', '2,4', null, '1', '0', '0', '1.0', '1', '1530420140', '1530524728');
INSERT INTO `lt_user_follow_data` VALUES ('', 'TedGod', 'X,0,X,8@X,8,X,0@X,1,X,7@X,7,X,1@X,2,X,6@X,6,X,2@X,3,X,5@X,5,X,3@X,4,X,4', '8', '1', '2,4', null, '1', '0', '0', '1.0', '1', '1530420140', '1530524728');
INSERT INTO `lt_user_follow_data` VALUES ('', 'TedGod', '0,8,X,X@8,0,X,X@1,7,X,X@7,1,X,X@2,6,X,X@6,2,X,X@3,5,X,X@5,3,X,X@4,4,X,X', '8', '1', '1,2', null, '1', '0', '1', '1.0', '1', '1530022446', '1530553079');
INSERT INTO `lt_user_follow_data` VALUES ('', 'TedGod', 'X,X,0,8@X,X,8,0@X,X,1,7@X,X,7,1@X,X,2,6@X,X,6,2@X,X,3,5@X,X,5,3@X,X,4,4', '8', '1', '3,4', null, '1', '0', '0', '0.5', '1', '1530174775', '1530455175');
INSERT INTO `lt_user_follow_data` VALUES ('', 'TedGod', '0,X,8,X@8,X,0,X@1,X,7,X@7,X,1,X@2,X,6,X@6,X,2,X@3,X,5,X@5,X,3,X@4,X,4,X', '8', '1', '1,3', null, '1', '0', '0', '0.3', '1', '1530260386', '1530260421');
INSERT INTO `lt_user_follow_data` VALUES ('', 'TedGod', 'X,0,8,X@X,8,0,X@X,1,7,X@X,7,1,X@X,2,6,X@X,6,2,X@X,3,5,X@X,5,3,X@X,4,4,X', '8', '1', '2,3', null, '1', '0', '0', '0.3', '1', '1530260439', '1530459034');
INSERT INTO `lt_user_follow_data` VALUES ('', 'TedGod', '0,X,9,X@9,X,0,X@1,X,8,X@8,X,1,X@2,X,7,X@7,X,2,X@3,X,6,X@6,X,3,X@4,X,5,X@5,X,4,X', '9', '1', '1,3', null, '1', '0', '0', '1.0', '1', '1530277795', '1530321496');
INSERT INTO `lt_user_follow_data` VALUES ('', '六六大顺888', '0,X,9,X@9,X,0,X@1,X,8,X@8,X,1,X@2,X,7,X@7,X,2,X@3,X,6,X@6,X,3,X@4,X,5,X@5,X,4,X', '9', '1', '1,3', null, '1', '0', '0', '0.5', '1', '1530284554', '1530343621');
INSERT INTO `lt_user_follow_data` VALUES ('', '六六大顺888', 'X,X,0,8@X,X,8,0@X,X,1,7@X,X,7,1@X,X,2,6@X,X,6,2@X,X,3,5@X,X,5,3@X,X,4,4', '8', '1', '3,4', null, '1', '0', '0', '2.0', '1', '1530296068', '1530296250');
INSERT INTO `lt_user_follow_data` VALUES ('', 'babo', '0,X,X,6@6,X,X,0@1,X,X,5@5,X,X,1@2,X,X,4@4,X,X,2@3,X,X,3', '6', '1', '1,4', null, '1', '0', '0', '3.0', '1', '1530371733', '1530546311');
INSERT INTO `lt_user_follow_data` VALUES ('', 'babo', 'X,0,X,6@X,6,X,0@X,1,X,5@X,5,X,1@X,2,X,4@X,4,X,2@X,3,X,3', '6', '1', '2,4', null, '1', '0', '1', '2.0', '1', '1530374875', '1530549162');
INSERT INTO `lt_user_follow_data` VALUES ('', 'babo', '0,8,X,X@8,0,X,X@1,7,X,X@7,1,X,X@2,6,X,X@6,2,X,X@3,5,X,X@5,3,X,X@4,4,X,X', '8', '1', '1,2', null, '1', '0', '1', '1.0', '1', '1530375533', '1530549173');
INSERT INTO `lt_user_follow_data` VALUES ('', 'babo', '2,9,X,X@9,2,X,X@3,8,X,X@8,3,X,X@4,7,X,X@7,4,X,X@5,6,X,X@6,5,X,X', '11', '1', '1,2', null, '1', '0', '0', '0.5', '1', '1530375690', '1530543524');
INSERT INTO `lt_user_follow_data` VALUES ('', 'babo', '0,X,7,X@7,X,0,X@1,X,6,X@6,X,1,X@2,X,5,X@5,X,2,X@3,X,4,X@4,X,3,X', '7', '1', '1,3', null, '1', '0', '0', '0.5', '1', '1530376528', '1530459489');
INSERT INTO `lt_user_follow_data` VALUES ('', 'babo', '0,X,X,8@8,X,X,0@1,X,X,7@7,X,X,1@2,X,X,6@6,X,X,2@3,X,X,5@5,X,X,3@4,X,X,4', '8', '1', '1,4', null, '1', '0', '0', '0.5', '1', '1530376780', '1530417306');
INSERT INTO `lt_user_follow_data` VALUES ('', 'babo', '1,X,9,X@9,X,1,X@2,X,8,X@8,X,2,X@3,X,7,X@7,X,3,X@4,X,6,X@6,X,4,X@5,X,5,X', '10', '1', '1,3', null, '1', '0', '0', '0.5', '1', '1530378305', '1530540619');
INSERT INTO `lt_user_follow_data` VALUES ('', 'babo', 'X,0,X,8@X,8,X,0@X,1,X,7@X,7,X,1@X,2,X,6@X,6,X,2@X,3,X,5@X,5,X,3@X,4,X,4', '8', '1', '2,4', null, '1', '0', '0', '1.0', '1', '1530413011', '1530523574');
INSERT INTO `lt_user_follow_data` VALUES ('', '六六大顺888', '0,X,X,8@8,X,X,0@1,X,X,7@7,X,X,1@2,X,X,6@6,X,X,2@3,X,X,5@5,X,X,3@4,X,X,4', '8', '1', '1,4', null, '1', '0', '0', '3.0', '1', '1530418598', '1530463837');
INSERT INTO `lt_user_follow_data` VALUES ('', '六六大顺888', 'X,0,X,8@X,8,X,0@X,1,X,7@X,7,X,1@X,2,X,6@X,6,X,2@X,3,X,5@X,5,X,3@X,4,X,4', '8', '1', '2,4', null, '1', '0', '0', '1.0', '1', '1530420088', '1530421993');
INSERT INTO `lt_user_follow_data` VALUES ('', 'babo', 'X,X,1,9@X,X,9,1@X,X,2,8@X,X,8,2@X,X,3,7@X,X,7,3@X,X,4,6@X,X,6,4@X,X,5,5', '10', '1', '3,4', null, '1', '0', '0', '0.5', '1', '1530425485', '1530540898');
INSERT INTO `lt_user_follow_data` VALUES ('', 'babo', 'X,X,0,7@X,X,7,0@X,X,1,6@X,X,6,1@X,X,2,5@X,X,5,2@X,X,3,4@X,X,4,3', '7', '1', '3,4', null, '1', '0', '0', '0.5', '1', '1530438069', '1530534927');
INSERT INTO `lt_user_follow_data` VALUES ('', '六六大顺888', '0,8,X,X@8,0,X,X@1,7,X,X@7,1,X,X@2,6,X,X@6,2,X,X@3,5,X,X@5,3,X,X@4,4,X,X', '8', '1', '1,2', null, '1', '0', '1', '1.0', '1', '1530468754', '1530554817');
INSERT INTO `lt_user_follow_data` VALUES ('', '六六大顺888', 'X,0,X,9@X,9,X,0@X,1,X,8@X,8,X,1@X,2,X,7@X,7,X,2@X,3,X,6@X,6,X,3@X,4,X,5@X,5,X,4', '9', '1', '2,4', null, '1', '0', '0', '1.0', '1', '1530548124', '1530554778');
INSERT INTO `lt_user_follow_data` VALUES ('', '六六大顺888', '1,X,9,X@9,X,1,X@2,X,8,X@8,X,2,X@3,X,7,X@7,X,3,X@4,X,6,X@6,X,4,X@5,X,5,X', '10', '1', '1,3', null, '1', '0', '0', '0.1', '1', '1530541683', '1530541683');
