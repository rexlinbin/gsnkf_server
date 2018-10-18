set names utf8;
create table if not exists t_guild_rob_user
(
	uid int unsigned not null comment '军团成员ID，主键',
	rob_id int unsigned not null comment '所在抢粮战ID，处在抢粮战时，标示所在抢粮战ID（抢夺军团ID），其他时候为0',
	guild_id int unsigned not null comment '玩家所在军团ID',
	uname varchar(16) not null comment '用户名字,被改动以后需要刷新',
	remove_cd_num int unsigned not null comment '消除战斗冷却次数',
	speedup_num int unsigned not null comment '加速次数',
	kill_num int unsigned not null comment '本次战斗中获得总杀敌数',
	user_grain_num int unsigned not null comment '本次战斗中个人获得的粮草数',
	guild_grain_num int unsigned not null comment '本次战斗中为军团抢夺的粮草数',
	merit_num int unsigned not null comment '本次战斗中获得的功勋数',
	contr_num int unsigned not null comment '本次战斗中获得的个人贡献数',
	reward_time int unsigned not null comment '本次战斗结束按排行榜发奖的时间',
	join_time int unsigned not null comment '加入抢粮战的时间',
	kill_time int unsigned not null comment '最后击杀的时间',
	offline_time int unsigned not null comment '点离线的时间',
	primary key(uid),
	index(rob_id)
)engine = InnoDb default charset utf8;