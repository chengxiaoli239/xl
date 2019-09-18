/*
Navicat MySQL Data Transfer

Source Server         : 154.83.17.96(香港)
Source Server Version : 50643
Source Host           : 154.83.17.96:3306
Source Database       : lottery_xl

Target Server Type    : MYSQL
Target Server Version : 50643
File Encoding         : 65001

Date: 2019-05-17 14:07:09
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `lt_ssc_sd_hz_val`
-- ----------------------------
DROP TABLE IF EXISTS `lt_ssc_sd_hz_val`;
CREATE TABLE `lt_ssc_sd_hz_val` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `val` varchar(120) DEFAULT NULL COMMENT '和值范围',
  `status` tinyint(1) DEFAULT '1' COMMENT '是否显示0不显示1显示',
  `created_at` int(11) DEFAULT NULL COMMENT '创建时间',
  `updated_at` int(11) DEFAULT NULL COMMENT '更新时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `val` (`val`)
) ENGINE=MyISAM AUTO_INCREMENT=45 DEFAULT CHARSET=utf8 COMMENT='四定和值统计基本表';

-- ----------------------------
-- Records of lt_ssc_sd_hz_val
-- ----------------------------
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '1,2,3,4,5,6', '1', '1534154278', '1534154278', '2019-03-24 10:18:48');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '5,6,7,8,9,10', '1', '1534154278', '1534154278', '2019-03-24 10:18:48');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '11,12,13,14,15', '1', '1534154278', '1534154278', '2019-03-22 17:01:15');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '16,17,18,19', '1', '1534154278', '1534154278', '2019-03-22 17:01:29');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '20,21,22,23,24', '1', '1534154278', '1534154278', '2019-03-22 17:01:43');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '25,26,27,28,29', '1', '1534154278', '1534154278', '2019-03-24 13:05:43');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '30,31,32,33,34,35', '1', '1534154278', '1534154278', '2019-04-02 09:10:16');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '5,7,9,11,13,15', '1', '1534154278', '1534154278', '2019-04-24 11:41:08');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '6,8,10,12,14,16', '1', '1534154278', '1534154278', '2019-04-24 11:41:09');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '17,19,21,23,25,27', '1', '1534154278', '1534154278', '2019-04-24 11:41:09');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '18,20,22,24,26,28', '1', '1534154278', '1534154278', '2019-04-24 11:41:09');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '35', '0', '1534154278', '1534154278', '2019-04-30 13:33:18');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '34', '0', '1534154278', '1534154278', '2019-04-30 16:54:55');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '33', '1', '1534154278', '1534154278', '2019-05-17 14:05:37');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '32', '1', '1534154278', '1534154278', '2019-05-17 14:05:41');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '31', '1', '1534154278', '1534154278', '2019-05-17 14:05:41');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '30', '1', '1534154278', '1534154278', '2019-04-23 16:55:39');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '29', '1', '1534154278', '1534154278', '2019-04-23 16:55:38');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '28', '1', '1534154278', '1534154278', '2019-04-23 16:55:38');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '27', '1', '1534154278', '1534154278', '2019-04-23 16:55:37');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '26', '1', '1534154278', '1534154278', '2019-04-23 16:55:37');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '25', '1', '1534154278', '1534154278', '2019-04-23 16:55:36');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '24', '1', '1534154278', '1534154278', '2019-04-24 08:59:51');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '23', '1', '1534154278', '1534154278', '2019-04-24 18:38:38');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '22', '1', '1534154278', '1534154278', '2019-04-24 18:38:40');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '21', '1', '1534154278', '1534154278', '2019-04-24 18:38:44');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '20', '1', '1534154278', '1534154278', '2019-04-24 18:38:46');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '19', '1', '1534154278', '1534154278', '2019-04-24 18:38:48');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '18', '1', '1534154278', '1534154278', '2019-04-24 18:38:59');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '17', '1', '1534154278', '1534154278', '2019-04-24 18:39:00');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '16', '1', '1534154278', '1534154278', '2019-04-24 18:39:03');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '15', '1', '1534154278', '1534154278', '2019-04-24 18:39:05');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '14', '1', '1534154278', '1534154278', '2019-05-17 13:52:19');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '13', '1', '1534154278', '1534154278', '2019-05-17 13:52:22');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '12', '1', '1534154278', '1534154278', '2019-05-17 13:52:23');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '11', '1', '1534154278', '1534154278', '2019-05-17 13:52:25');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '10', '1', '1534154278', '1534154278', '2019-05-17 13:52:28');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '9', '1', '1534154278', '1534154278', '2019-05-17 13:52:29');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '8', '1', '1534154278', '1534154278', '2019-05-17 13:52:30');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '7', '1', '1534154278', '1534154278', '2019-05-17 13:52:33');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '6', '1', '1534154278', '1534154278', '2019-05-17 13:52:34');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '5', '1', '1534154278', '1534154278', '2019-05-17 13:52:36');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '4', '1', '1534154278', '1534154278', '2019-05-17 13:52:37');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '3', '1', '1534154278', '1534154278', '2019-05-17 13:52:39');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '2', '1', '1534154278', '1534154278', '2019-05-17 13:52:42');
INSERT INTO `lt_ssc_sd_hz_val` VALUES ('', '1', '1', '1534154278', '1534154278', '2019-05-17 13:52:57');
