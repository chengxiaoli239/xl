-- 计划利润统计分组（与 console/migrations/m260323_120000_create_plan_profit_stat_groups.php 一致）
CREATE TABLE IF NOT EXISTS `lt_plan_profit_stat_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL COMMENT '所属用户',
  `lottery_type` int(11) NOT NULL COMMENT '彩种类型，与计划列表一致',
  `name` varchar(64) NOT NULL COMMENT '分组名称',
  `created_at` int(11) NOT NULL DEFAULT '0',
  `updated_at` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_ppsg_uid_lottery` (`uid`,`lottery_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `lt_plan_profit_stat_group_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL COMMENT '分组id',
  `plan_id` int(11) NOT NULL COMMENT '计划id',
  `created_at` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ppsgp_plan` (`plan_id`),
  UNIQUE KEY `uk_ppsgp_group_plan` (`group_id`,`plan_id`),
  KEY `idx_ppsgp_group` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
