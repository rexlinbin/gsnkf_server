set names utf8;
create table if not exists t_rob_tomb(
	uid int unsigned not null comment '用户id',
	today_free_num int unsigned not null comment '今天免费挖宝次数',
	today_gold_num int unsigned not null comment '今天金币挖宝次数',
	last_refresh_time int unsigned not null comment '上次刷新挖宝数据的时间',
	accum_free_num int unsigned not null comment '活动期间内免费挖宝次数',
	accum_gold_num int unsigned not null comment '活动期间内金币挖宝次数',
	va_rob_tomb blob not null comment '',
	primary key(uid)
)engine = InnoDb default charset utf8;