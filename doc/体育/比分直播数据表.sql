CREATE TABLE `lt_events_live_datas` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL COMMENT '用户id',
  `event_id` int(1) DEFAULT '0' COMMENT '比赛项目id',
  `clock_minute` int(11) DEFAULT '0' COMMENT '比赛进行分钟数',
  `clock_second` int(11) DEFAULT '0' COMMENT '当前分钟秒数',
  `clock_minutesLeftInPeriod` int(11) DEFAULT '0' COMMENT '场次剩余分钟',
  `clock_secondsLeftInMinute` int(11) DEFAULT '0' COMMENT '当前分钟剩余秒数',
  `clock_period` int(11) DEFAULT '0' COMMENT '当前节数',
  `clock_running` tinyint(11) DEFAULT '0' COMMENT '是否在进行',
  `score_home` int(11) DEFAULT '0' COMMENT '主队得分',
  `score_away` int(11) DEFAULT '0' COMMENT '客队得分',
  `score_info` varchar(64) DEFAULT '0' COMMENT '比分情况',
  `score_who` varchar(64) DEFAULT '0' COMMENT '得分方',
  `statics_football_home_yellowCards` varchar(64) DEFAULT '0' COMMENT '主队黄牌数',
  `statics_football_way_yellowCards` varchar(64) DEFAULT '0' COMMENT '主队黄牌数',
  `statics_football_home_redCards` varchar(64) DEFAULT '0' COMMENT '主队红牌数',
  `statics_football_way_redCards` varchar(64) DEFAULT '0' COMMENT '客队红牌数',
  `statics_football_home_corners` varchar(64) DEFAULT '0' COMMENT '主队角球数',
  `statics_football_way_corners` varchar(64) DEFAULT '0' COMMENT '客队角球数',
  `liveStatistics` text DEFAULT '' COMMENT '直播数据统计',

  `created_at` int(11) DEFAULT NULL COMMENT '创建时间',
  `updated_at` int(11) NOT NULL COMMENT '更新时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='体育直播比分数据';



