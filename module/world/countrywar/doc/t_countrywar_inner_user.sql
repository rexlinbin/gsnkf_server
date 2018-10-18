set names utf8;

create table if not exists t_countrywar_inner_user
(
		pid                   	int unsigned not null comment 'pid',                             
		server_id				int unsigned not null comment 'server_id',
		support_pid             int unsigned not null comment '决赛助威的玩家pid',
		support_server_id		int unsigned not null comment '决赛助威的serverId',
		support_side		    int unsigned not null comment '决赛助威的势力',
		worship_time			int unsigned not null comment '膜拜时间',
		audition_reward_time	int unsigned not null comment '初赛排名奖励发放时间',
		support_reward_time		int unsigned not null comment '助威奖励发放时间',         				
		final_reward_time 		int unsigned not null comment '决赛奖励发放时间',
		update_time				int unsigned not null comment '更新时间',

    primary key(pid,server_id)
)engine = InnoDb default charset utf8;