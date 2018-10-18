set names utf8;

create table if not exists t_roulette(
		uid                int unsigned not null comment '用户id',
		today_free_num     int unsigned not null comment '今日已用免费次数',
		accum_free_num     int unsigned not null comment '活动期间已用免费次数',
		accum_gold_num     int unsigned not null comment '活动期间已用金币次数',
		integeral          int unsigned not null comment '活动期间获得的积分',
		last_refresh_time  int unsigned not null comment '最后一次刷新的时间',
		last_roll_time     int unsigned not null comment '最后一次抽奖的时间',
		va_boxreward       blob not null         comment '已经领取的宝箱号',
		primary key(uid),
		index last_refresh_time(last_refresh_time)
)engine = InnoDb default charset utf8;