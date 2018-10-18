SET NAMES UTF8;

CREATE TABLE IF NOT EXISTS t_copy_team
(
	uid					int(10) unsigned not null comment 'uid',
	cur_guild_copy		int unsigned not null comment '当前的公会副本,此副本前面的一个副本通关了，此副本不一定能打，此副本也有可能已经通关了',
	guild_rfr_time 		int unsigned not null comment '上次刷新公会的时间',
	guild_atk_num	 	int unsigned not null comment '当天公会组队次数',
	guild_help_num		int unsigned not null comment '当天公会组队协助次数',
	buy_atk_num			int unsigned not null comment '购买组队次数的次数',
	invite_status		int	unsigned not null comment '邀请状态1.允许所有人邀请 2.只允许同工会的人邀请',
	va_copy_team		blob not null comment 'cur_passed_guild_copy=>int当前最大的通关副本',
	primary key(uid)
)default charset utf8 engine = InnoDb;