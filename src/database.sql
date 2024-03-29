CREATE TABLE IF NOT EXISTS `user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(20) unsigned NOT NULL DEFAULT '0' COMMENT '邀请人ID',
  `pids` varchar(500) NOT NULL DEFAULT '' COMMENT '邀请人uid,按层级从下往上排列的uid数组,即第一个是直接上级',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号',
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT '邮箱',
  `password` char(32) NOT NULL COMMENT '密码',
  `salt` varchar(10) NOT NULL COMMENT '加密盐',
  `pay_password` char(32) NOT NULL DEFAULT '' COMMENT '交易密码',
  `pay_salt` varchar(10) NOT NULL DEFAULT '' COMMENT '交易密码加密盐',
  `token` char(32) NOT NULL DEFAULT '' COMMENT '登录token',
  `nickname` varchar(30) NOT NULL DEFAULT '' COMMENT '昵称',
  `level` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '会员等级',
  `score` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '可用积分',
  `freeze_score` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '冻结积分',
  `amount` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '可用金额',
  `freeze_amount` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '冻结金额',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
  `sex` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '性别 0-保密 1-男 2-女',
  `comment` varchar(255) NOT NULL DEFAULT '' COMMENT '用户描述',
  `address_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '默认地址ID',
  `login_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `login_ip` varchar(20) NOT NULL DEFAULT '' COMMENT '最后登录的IP',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态 0-禁用 1-正常 2-待审核 3-审核未通过',
  `update_time` int(10) unsigned NOT NULL COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '用户表' ROW_FORMAT = Compact;

CREATE TABLE IF NOT EXISTS `user_certification` (
  `uid` int(10) unsigned NOT NULL COMMENT '用户ID',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '用户类型 0-个人用户 1-企业用户',
  `name` varchar(250) NOT NULL COMMENT '真实姓名/企业名称',
  `identity` varchar(250) NOT NULL COMMENT '身份证号码/营业执照号码',
  `person` varchar(50) NOT NULL COMMENT '联系人姓名',
  `mobile` varchar(50) NOT NULL COMMENT '联系人手机号码',
  `email` varchar(100) NOT NULL COMMENT '联系人邮箱',
  `paper_front` varchar(250) NOT NULL DEFAULT '' COMMENT '身份证正面照URL',
  `paper_back` varchar(250) NOT NULL DEFAULT '' COMMENT '身份证反面照URL',
  `paper_hand` varchar(250) NOT NULL DEFAULT '' COMMENT '手持身份证照片URL',
  `license` varchar(250) NOT NULL DEFAULT '' COMMENT '营业执照URL',
  `comment` varchar(250) NOT NULL DEFAULT '' COMMENT '备注信息',
  `approved_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '认证通过时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '认证状态 0-待认证 1-认证通过 2-认证失败',
  `update_time` int(10) unsigned NOT NULL COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`uid`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '用户实名认证表' ROW_FORMAT = Compact;

CREATE TABLE IF NOT EXISTS `user_address` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL COMMENT '用户ID',
  `name` varchar(50) NOT NULL COMMENT '收货人姓名',
  `mobile` varchar(20) NOT NULL COMMENT '收货人手机号',
  `pca` varchar(250) NOT NULL COMMENT '省份城市区县地址, 可使用_分割',
  `address` varchar(200) NOT NULL COMMENT '具体的地址门牌号',
  `pcode` varchar(20) NOT NULL DEFAULT '' COMMENT '邮编',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '0-无效 1-正常',
  `update_time` int(10) unsigned NOT NULL COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `user`(`uid`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '用户地址表' ROW_FORMAT = Compact;

CREATE TABLE IF NOT EXISTS `user_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL COMMENT '用户ID',
  `sid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联记录ID',
  `method` varchar(20) NOT NULL COMMENT '请求方式',
  `path` varchar(200) NOT NULL COMMENT '请求路径',
  `action` varchar(50) NOT NULL COMMENT '操作类型',
  `content` varchar(1000) NOT NULL DEFAULT '' COMMENT '操作内容',
  `ua` varchar(255) NOT NULL DEFAULT '' COMMENT '浏览器请求头user-agent',
  `ip` varchar(20) NOT NULL COMMENT '操作IP',
  `create_time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `uid`(`uid`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '用户操作日志表' ROW_FORMAT = COMPACT;

CREATE TABLE IF NOT EXISTS `user_login_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `uid` int(10) unsigned NOT NULL COMMENT '用户ID',
  `type` tinyint(3) unsigned NOT NULL DEFAULT 1 COMMENT '日志类型 0-退出登录 1-登录成功 2-密码错误',
  `action` varchar(100) NOT NULL COMMENT '操作类型',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '描述信息',
  `ip` varchar(20) NOT NULL DEFAULT '' COMMENT 'IP地址',
  `ua` varchar(250) NOT NULL DEFAULT '' COMMENT '浏览器请求头user-agent',
  `create_time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `uid`(`uid`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '用户登录日志表' ROW_FORMAT = COMPACT;

CREATE TABLE IF NOT EXISTS `user_assets_log` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
  `sid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联记录ID',
  `cate` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '资产类型: 0-积分 1-金额',
  `from` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '来源用户ID',
  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '类型: 0-用户充值 1-系统充值 2-系统扣减 3-用户转入 4-用户转出 5-冻结转可用 6-可用转冻结',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '描述信息',
  `available_before` int(10) UNSIGNED NOT NULL COMMENT '操作前数量',
  `available_num` int(10) UNSIGNED NOT NULL COMMENT '操作数量',
  `available_after` int(10) UNSIGNED NOT NULL COMMENT '操作后数量',
  `freeze_before` int(10) UNSIGNED NOT NULL COMMENT '操作前冻结数量',
  `freeze_num` int(10) UNSIGNED NOT NULL COMMENT '操作冻结数量',
  `freeze_after` int(10) UNSIGNED NOT NULL COMMENT '操作后冻结数量',
  `create_time` int(10) UNSIGNED NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `user`(`uid`)
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '资产流水表' ROW_FORMAT = COMPACT;

CREATE TABLE IF NOT EXISTS `user_signin` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` int(10) UNSIGNED NOT NULL COMMENT '用户ID',
  `day` date NOT NULL COMMENT '签到日期',
  `score` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '奖励积分',
  `update_time` int(10) UNSIGNED NOT NULL COMMENT '创建时间',
  `create_time` int(10) UNSIGNED NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `user`(`uid`),
  UNIQUE INDEX `item`(`uid`, `day`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '用户签到表' ROW_FORMAT = COMPACT;