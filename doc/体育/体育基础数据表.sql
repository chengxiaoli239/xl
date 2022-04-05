CREATE TABLE `lt_score_datas` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL COMMENT '用户id',
  `cate1_id` int(1) DEFAULT '0' COMMENT '一级分类id:groups.id',
  `cate1_name` varchar(64) DEFAULT '0' COMMENT '一级分类名称:groups.name',
  `cate2_id` tinyint(1) DEFAULT '0' COMMENT '二级分类id:liveEvents.path.id',
  `cate2_name` varchar(64) DEFAULT '0' COMMENT '二级分类名称:groups.name',
  `cate3_id` tinyint(1) DEFAULT '0' COMMENT '三级分类id:liveEvents.path.id',
  `cate3_name` varchar(64) DEFAULT '0' COMMENT '三级分类名称:groups.name',
  `cate_name` tinyint(1) DEFAULT '0' COMMENT '分类名称:groups.name',
  `sport_name` tinyint(1) DEFAULT '0' COMMENT '球类名称:FOOTBALL、TENNIS',
  `event_id` tinyint(1) DEFAULT '0' COMMENT '比赛项目id',
  `groupId` tinyint(1) DEFAULT '0' COMMENT '组id',
  `groupName` varchar(64) DEFAULT NULL COMMENT '组名',
  `liveBoCount` int(11) DEFAULT NULL COMMENT 'liveBoCount',
  `homeName` varchar(64) DEFAULT NULL COMMENT '主队名字',
  `wayName` varchar(64) DEFAULT NULL COMMENT '客队名字',
  `englistName` varchar(255) DEFAULT NULL COMMENT '比赛主客对名称',
  `odd_home` decimal(10,2) DEFAULT NULL COMMENT '主队赔率',
  `odd_way` decimal(10,2) DEFAULT NULL COMMENT '客队赔率',
  `odd_draw` decimal(10,2) DEFAULT NULL COMMENT '平局赔率',
  `score_home` decimal(10,2) DEFAULT NULL COMMENT '主队得分',
  `score_way` decimal(10,2) DEFAULT NULL COMMENT '客队得分',
  `score_info` int(11) DEFAULT NULL COMMENT '比分详情',
  `clock_minute` int(11) DEFAULT NULL COMMENT '比赛耗时',	
  `clock_period` tinyint(1) DEFAULT '0' COMMENT '比赛节数',
  `clock_minute` int(11) DEFAULT NULL COMMENT '比赛耗时',
  `clock_secondsLeftInMinute` int(11) DEFAULT NULL COMMENT '比赛剩余分钟',



  `created_at` int(11) DEFAULT NULL COMMENT '创建时间',
  `updated_at` int(11) NOT NULL COMMENT '更新时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8 COMMENT='体育比分数据';



KambiBC-event-item KambiBC-event-item-1018566937 KambiBC-event-item--sport-FOOTBALL KambiBC-event-item--type-match KambiBC-event-item--live