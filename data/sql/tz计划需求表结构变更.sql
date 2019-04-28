DROP TABLE IF EXISTS `lt_plan_type`;
CREATE TABLE `lt_plan_type` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type_name` varchar(64) DEFAULT NULL COMMENT '类型名称',
  `playway` tinyint(4) NOT NULL DEFAULT '10' COMMENT '投注方式,对应0898投注网',
  `created_at` int(11) DEFAULT NULL COMMENT '创建时间',
  `updated_at` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `playway` (`playway`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='计划投注类型表';

DROP TABLE IF EXISTS `lt_user_plans`;
CREATE TABLE `lt_user_plans` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type_name` varchar(64) DEFAULT NULL COMMENT '类型名称',
  `account` varchar(64) DEFAULT NULL COMMENT '用户账号'
  `created_at` int(11) DEFAULT NULL COMMENT '创建时间',
  `updated_at` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `playway` (`playway`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='计划投注类型表';

DROP TABLE IF EXISTS `lt_user_plan_2d`;
CREATE TABLE `lt_user_plan_2d` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `account` varchar(64) DEFAULT NULL COMMENT '用户账号',
  `code` varchar(640) NOT NULL COMMENT '投注号码',
  `position` varchar(64) DEFAULT '' COMMENT '定位位置',
  `is_follow` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否追号',
  `is_simulate` tinyint(4) DEFAULT '1' COMMENT '是否模拟',
  `single` float(4,1) DEFAULT '1.0' COMMENT '投注倍数(元/注)',
  `status` tinyint(4) DEFAULT '1' COMMENT '是否激活',
  `created_at` int(11) DEFAULT NULL COMMENT '创建时间',
  `updated_at` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `account` (`account`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='投注计划二定表';

DROP TABLE IF EXISTS `lt_user_plan_3d`;
CREATE TABLE `lt_user_plan_3d` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `account` varchar(64) DEFAULT NULL COMMENT '用户账号',
  `code` varchar(640) NOT NULL COMMENT '投注号码',
  `position` varchar(64) DEFAULT '' COMMENT '定位位置',
  `is_follow` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否追号',
  `is_simulate` tinyint(4) DEFAULT '1' COMMENT '是否模拟',
  `single` float(4,1) DEFAULT '1.0' COMMENT '投注倍数(元/注)',
  `status` tinyint(4) DEFAULT '1' COMMENT '是否激活',
  `created_at` int(11) DEFAULT NULL COMMENT '创建时间',
  `updated_at` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `account` (`account`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='投注计划三定表';

CREATE TABLE `lt_user_site` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(8) DEFAULT NULL COMMENT '用户ID',
  `stie_url` tinyint(4) DEFAULT NULL COMMENT '和值',
  `stie_name` tinyint(4) DEFAULT NULL COMMENT '和值',
  `stie_id` tinyint(4) DEFAULT NULL COMMENT 'zhan',
  `current_miss` tinyint(1) DEFAULT NULL COMMENT '本期遗漏',
  `last_time_miss` tinyint(4) DEFAULT NULL COMMENT '上次遗漏',
  `last_time_miss_range` varchar(64) DEFAULT NULL COMMENT '上次遗漏范围',
  `max_miss` tinyint(4) DEFAULT NULL COMMENT '最大遗漏(近200期)',
  `max_range` varchar(64) DEFAULT NULL COMMENT '最大遗漏范围(近200期)',
  `yl_records` varchar(255) DEFAULT NULL COMMENT '遗漏记录',
  `history_max_miss` tinyint(4) DEFAULT NULL COMMENT '历史最大遗漏',
  `updated_at` int(11) DEFAULT NULL COMMENT '更新时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `positions` (`positions`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8 COMMENT='d';

