set names utf8;

create table if not exists t_compete 
(
	uid	int unsigned not null comment '用户id',
	num	int	unsigned not null comment '当天比武次数，每天重置',
	buy int	unsigned not null comment '当天购买次数，每天重置',
	honor int unsigned not null comment '荣誉值',
	point int unsigned not null comment '积分',
	last_point int unsigned not null comment '上一轮积分',
	point_time int unsigned not null comment '上次积分时间',
	compete_time int unsigned not null comment '比武时间',
	refresh_time int unsigned not null comment '刷新CD时间',
	reward_time int unsigned not null comment '发奖时间',
	va_compete blob not null comment '对手列表：rival(uid,uid...),仇人列表：foe(uid,uid...)',
	primary key(uid),
	index point_point_time(point, point_time)
)default charset utf8 engine = InnoDb;