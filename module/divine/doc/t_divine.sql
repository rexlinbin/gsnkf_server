set names utf8;

CREATE TABLE IF NOT EXISTS t_divine(

	uid 				int unsigned not null comment 'uid',
	divi_times 			int unsigned not null comment '今日占星次数',
	refresh_time 		int unsigned not null comment '上次刷新时间',
	free_refresh_num 	int unsigned not null comment '免费刷新次数',
	prize_step 			int unsigned not null comment '奖励已领取的次数',
	target_finish_num 	int unsigned not null comment '目标星座完成次数',
	integral 			int unsigned not null comment '占星积分',
	prize_level 		int unsigned not null comment '奖励表级别',
	ref_prize_num		int unsigned not null comment '今日奖励刷新次数',
	va_divine 			blob not null comment ' 目标星座配备 占星星座配备 array( target => array(),own => array(),lighted = array())',

	primary key(uid)
)engine = InnoDb default charset utf8;