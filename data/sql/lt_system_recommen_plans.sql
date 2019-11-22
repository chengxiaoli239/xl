CREATE TABLE `lt_system_recommen_plans` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `playway` tinyint(4) NOT NULL DEFAULT '2' COMMENT '投注方式:1二字定2三字定3四字定',
  `tz_type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '投注类型:lt_tz_types表',
  `is_test` tinyint(1) DEFAULT '0' COMMENT '是否为系统测试计划',
  `lottery_type` tinyint(1) DEFAULT '5' COMMENT '彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc',
  `current_profits` decimal(10,2) DEFAULT NULL COMMENT '当前盈利',
  `created_at` int(11) NOT NULL COMMENT '创建时间',
  `updated_at` int(11) NOT NULL COMMENT '更新时间',
  `update_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=799 DEFAULT CHARSET=utf8 COMMENT='系统推荐计划';