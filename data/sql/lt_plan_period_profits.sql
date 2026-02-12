-- 计划每期盈利记录表（每期开奖后一条，用于对账与排查）
CREATE TABLE IF NOT EXISTS `lt_plan_period_profits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) NOT NULL COMMENT '计划id',
  `qihao` varchar(64) NOT NULL COMMENT '期号',
  `profit_before` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '开奖前累计盈利金额',
  `profit_change` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '本期盈亏金额（正=盈利，负=亏损）',
  `profit_after` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '开奖后累计盈利金额',
  `period_bet_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '本期投注金额（每计划每期一条）',
  `period_group_count` int(11) NOT NULL DEFAULT '0' COMMENT '本期组数（号码个数，如1234 3452为2组）',
  `period_multiple` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '本期倍数',
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `lottery_type` int(11) NOT NULL DEFAULT '8' COMMENT '彩种',
  `created_at` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plan_qihao` (`plan_id`,`qihao`),
  KEY `idx_plan_id` (`plan_id`),
  KEY `idx_qihao` (`qihao`),
  KEY `idx_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='计划每期盈利记录（开奖后前后盈利+投注金额/组数/倍数）';

-- 若表已存在且无新字段，可执行以下 ALTER 增加：本期投注金额、组数、倍数
-- ALTER TABLE `lt_plan_period_profits` ADD COLUMN `period_bet_amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '本期投注金额' AFTER `profit_after`;
-- ALTER TABLE `lt_plan_period_profits` ADD COLUMN `period_group_count` int(11) NOT NULL DEFAULT '0' COMMENT '本期组数(号码个数)' AFTER `period_bet_amount`;
-- ALTER TABLE `lt_plan_period_profits` ADD COLUMN `period_multiple` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '本期倍数' AFTER `period_group_count`;
