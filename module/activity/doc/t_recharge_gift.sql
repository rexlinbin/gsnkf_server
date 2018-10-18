set names utf8;
create table if not exists t_recharge_gift
(
	uid 		int unsigned 	not null comment '用户id',
	update_time	int unsigned 	not null comment '最后一次更新数据的时间,用来保证活动新一轮开始时数据刷新',
	va_reward 	blob 			not null comment '已经领取过的奖励id信息, array(rewardId => selectId)',
	primary key(uid)
)engine = InnoDb default charset utf8;