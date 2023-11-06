DROP TABLE IF EXISTS `lt_static_3d_user_profits_day`;
CREATE TABLE `lt_static_3d_user_profits_day` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` varchar(10) DEFAULT NULL COMMENT '日期',
  `user_id` int(11) DEFAULT '0' COMMENT '系统用户id(代理id)',
  `wechat_user_id` int(11) DEFAULT '0' COMMENT '微信用户表id',
  `bet_money` decimal(10,2) DEFAULT 0.00 COMMENT '日投注金额',
  `bonus` decimal(10,2) DEFAULT 0.00 COMMENT '中奖金额',
  `profits` decimal(10,2) DEFAULT 0.00 COMMENT '利润',
  `lottery_type` int(11) DEFAULT '5' COMMENT '彩种类型26福彩27排三',
  `created_at` int(11) DEFAULT NULL COMMENT '创建时间',
  `updated_at` int(11) NOT NULL COMMENT '更新时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `lottery_type` (`lottery_type`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='3d用户日利润统计表';
