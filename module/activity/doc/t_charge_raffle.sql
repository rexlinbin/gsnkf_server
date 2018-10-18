set names utf8;
create table if not exists t_charge_raffle(
	uid int unsigned not null comment '用户id',
	last_rfr_time int unsigned not null comment '刷新数据时间（每天刷新累计抽奖次数、今日抽奖次数、领取首冲奖励时间）',
	fetch_reward_time int unsigned not null comment '领取奖励时间',
	va_raffle_info blob not null comment '',
	primary key(uid)
)engine = InnoDb default charset utf8;