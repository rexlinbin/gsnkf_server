set names utf8;
create table if not exists t_ka(

	uid 						int unsigned not null comment '用户id',
	refresh_time 				int unsigned not null comment '刷新时间',
	point_today 			    int unsigned not null comment '今天的积分',
	point_add					int unsigned not null comment '今天累加的积分',
	primary key(uid)
)engine = InnoDb default charset utf8;