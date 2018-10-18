set names utf8;
create table if not exists t_mergeserver_reward(
	uid int unsigned not null comment 'uid',
	compensate_time int unsigned not null comment '补偿领取时间, 初始为0代表未领取, 否则为领取补偿时间',
	login_time int unsigned not null comment '活动期间内登陆时间',
	login_count int unsigned not null comment '活动期间内登陆次数',
	va_extra blob not null comment 'login_reward_got => array() 已经领取的累积登陆奖励的天数 recharge_reward_got => array() 已经领取的的充值返回的档位',
	primary key(uid)
)engine = InnoDb default charset utf8;