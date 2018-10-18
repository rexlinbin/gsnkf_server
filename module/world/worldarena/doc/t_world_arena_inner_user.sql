set names utf8;
create table if not exists t_world_arena_inner_user
(
	pid 				int unsigned not null comment '玩家pid',
	server_id 			int unsigned not null comment '所在服server_id',
	uid 				int unsigned not null comment '玩家uid',
	atked_num			int unsigned not null comment '已经攻击的次数',
	buy_atk_num 		int unsigned not null comment '已经购买的攻击次数',
	silver_reset_num 	int unsigned not null comment '银币重置次数',
	gold_reset_num 		int unsigned not null comment '金币重置次数',
	signup_time 		int unsigned not null comment '玩家报名时间',
	update_fmt_time		int unsigned not null comment '玩家更新战斗信息的时间',
    last_attack_time    int unsigned not null comment '玩家上次主动挑战的时间',
	update_time 		int unsigned not null comment '更新时间',
	va_fmt 				blob not null comment '玩家保存的战斗数据',
	va_extra 			blob not null comment '扩展信息inherit=>array(hid=>array(hp,rage))',
	primary key(pid,server_id)
)engine = InnoDb default charset utf8;