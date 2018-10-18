set names utf8;
CREATE TABLE IF NOT EXISTS t_friendlove(
	uid 		int unsigned not null comment '用户uid',
	num 		int unsigned not null comment '今日已经赠送次数',
	reftime		int unsigned not null comment '刷新时间',
	
	pk_num 		int unsigned not null comment '今日已经切磋的次数',
	bepk_num 	int unsigned not null comment '今日已经被切磋的次数',
	
	va_love		blob not null comment '获赠体力array( array( time, uid ), array( time, uid ), )',

	primary key(uid)
)engine = InnoDb default charset utf8;
