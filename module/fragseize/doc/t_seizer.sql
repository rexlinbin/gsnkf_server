set names utf8;

CREATE TABLE IF NOT EXISTS t_seizer(
	uid 				int unsigned not null comment 'uid',
	white_flag_time 	int unsigned not null comment '免战效果结束时间',
	first_time			int unsigned not null comment '第一次夺宝的时间（也就是新手阶段）',
	primary key(uid)
	
)engine = InnoDb default charset utf8;
