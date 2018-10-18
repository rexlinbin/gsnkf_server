set names utf8;
create table if not exists t_guild_rob
(
	guild_id int unsigned not null comment '抢粮军团ID，主键',
	defend_guild_id int unsigned not null comment '被抢军团ID，处在抢粮战阶段，标示被抢军团ID，其他时候为0',
	start_time int unsigned not null comment '抢粮战开始时间, 处在抢粮战阶段，标示抢粮战开始时间，其他时候为0',
	end_time int unsigned not null comment '抢粮战结束时间, 处在抢粮战阶段为0',
	stage int unsigned not null comment '抢粮所处阶段',
	total_rob_num int unsigned not null comment '抢夺的粮草数，处在抢粮战阶段，标示抢粮战中已经抢夺的粮食，其他时候为0',
	rob_limit int unsigned not null comment '本次抢粮战最多可以抢夺的粮草',
	va_extra blob not null comment 'spec_barn => array 蹲点粮仓的占领信息
										(
											int => array(uid => int, begin => int, arrhp => array(), timer => int),  array为空，则代表该位置没有人占领,id是已经占领的用户id，begin开始占领的时间，用于结算奖励
											int => array()，
										)',
	primary key(guild_id),
	index(defend_guild_id)
)engine = InnoDb default charset utf8;