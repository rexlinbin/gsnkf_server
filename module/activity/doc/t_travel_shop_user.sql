set names utf8;
create table if not exists t_travel_shop_user
(
	uid int unsigned not null comment '用户id',
	sum int unsigned not null comment '次数',
	score int unsigned not null comment '积分',
	start_time int unsigned not null comment '充值优惠开始时间',
	finish_time int unsigned not null comment '完成进度时间',
	refresh_time int unsigned not null comment '刷新时间',
	va_user blob not null comment 'buy{goodsId=>num},payback{$id},reward{$id}',
	primary key(uid),
	index refresh_time(refresh_time)
)engine = InnoDb default charset utf8;