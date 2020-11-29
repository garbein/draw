
CREATE DATABASE draw;

USE draw;

CREATE TABLE `user` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `access_token` varchar(32) NOT NULL DEFAULT '' COMMENT 'access token',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_mobile` (`mobile`),
  KEY `idx_access_token` (`access_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户';


CREATE TABLE `article` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `content` text NOT NULL COMMENT '内容',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='征文';

CREATE TABLE `user_prize` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `prize_id` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '状态id',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户抽中奖品';

CREATE TABLE `prize` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '奖品',
  `total_stock` int unsigned NOT NULL DEFAULT '0' COMMENT '总库存',
  `used_stock` int unsigned NOT NULL DEFAULT '0' COMMENT '已用库存',
  `draw_percent` tinyint NOT NULL DEFAULT '0' COMMENT '中奖概率',
  `rule` varchar(255) NOT NULL DEFAULT '' COMMENT '规则',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '状态',
  `update_time` int unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='奖品';

insert into prize (name, total_stock, draw_percent, rule) values 
('手机', 5, 1, '{"day_limit":1}'),
('电话卡', 100, 5, '{"user_limit":2}'),
('贴纸', 0, 94, '{"unlimit": 1}');
