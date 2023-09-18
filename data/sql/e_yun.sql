CREATE TABLE `lt_eyun_auth` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` smallint(6) NOT NULL DEFAULT '1' COMMENT '状态',
  `authorization` text COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'eyun授权key',
  `desc` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '备注信息',
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  `update_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='e云授权信息表';

CREATE TABLE `lt_robot_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(4) DEFAULT NULL COMMENT '管理员user.id',
  `wcId` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '微信原始id，永久不变，微信原始id （首次登录平台的号传""，掉线后必须传值，否则会频繁掉线！！！） 第三步会返回此字段，记得入库保存',
  `wId` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '登录实例标识 （本值非固定的，每次重新登录会返回新的，数据库记得实时更新wid）',
  `uuid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'uuid',
  `status` smallint(6) NOT NULL DEFAULT '1' COMMENT '状态',
  `desc` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '备注信息',
  `expire_time` int(11) DEFAULT NULL COMMENT '到期时间',
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  `update_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`) USING BTREE COMMENT 'user.id'
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='机器人用户表';

CREATE TABLE `lt_wechat_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(4) DEFAULT NULL COMMENT 'user.id,系统用户id',
  `userName` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '微信id，唯一',
  `nickName` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '昵称',
  `aliasName` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '微信号',
  `status` smallint(6) NOT NULL DEFAULT '1' COMMENT '状态-1禁用1启用',
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '余额',
  `is_credit` smallint(6) NOT NULL DEFAULT '1' COMMENT '是否信用用户0否1是',
  `bigHead` varchar(640) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '大头像',
  `smallHead` varchar(640) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '小头像',
  `labelList` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '标签列表',
  `remark` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '备注信息',
  `expire_time` int(11) DEFAULT NULL COMMENT '到期时间',
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  `update_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE COMMENT 'user.id',
  UNIQUE KEY `userName` (`userName`) USING BTREE COMMENT 'userName'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='机器人好友';

CREATE TABLE `lt_eyun_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(4) DEFAULT NULL COMMENT 'user.id,系统用户id',
  `toUser` varchar(64) NOT NULL DEFAULT '' COMMENT '接收微信id',
  `msgId` varchar(64) NOT NULL DEFAULT '' COMMENT '消息msgId',
  `newMsgId` varchar(64) NOT NULL DEFAULT '' COMMENT '消息newMsgId',
  `status` smallint(6) NOT NULL DEFAULT '0' COMMENT '状态0待处理处理中2处理成功3处理失败',
  `data` text not null  COMMENT '消息内容',
  `remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '备注信息',
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) NOT NULL,
  `update_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `msgId` (`toUser`,`newMsgId`, `msgId`),
  KEY `user_id` (`user_id`) USING BTREE COMMENT 'user.id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='e云消息表';
