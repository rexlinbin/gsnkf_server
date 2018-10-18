set names utf8;
create table if not exists t_world_compete_inner_user
(
	pid int unsigned not null comment "玩家pid",
	server_id int unsigned not null comment "所在服server_id",
	uid int unsigned not null comment "玩家uid",
	atk_num int unsigned not null comment "挑战完成次数，每日刷新",
	suc_num int unsigned not null comment "挑战胜利次数，每日刷新",
	buy_atk_num int unsigned not null comment "挑战购买次数，每日刷新",
	refresh_num int unsigned not null comment "对手刷新次数，每日刷新",
	worship_num int unsigned not null comment "膜拜完成次数，每日刷新",
	max_honor int unsigned not null comment "本次比武的最大荣誉，每周刷新",
	cross_honor int unsigned not null comment "跨服荣誉，累积比武的总荣誉，永不刷新",
	honor_time int unsigned not null comment "更新最大荣誉时间，",
	update_time int unsigned not null comment "更新时间，",
	reward_time int unsigned not null comment "发奖时间",
    va_extra blob not null comment "
									rival => array 战胜3个对手系统刷新或用户主动刷新
									{
										index => 3个对手
										{
                                       		pid 
											server_id
											status status为0是失败,1是成功
										}
									}
									prize => array 每日刷新
									{
										index => sucNum 已领取的奖励,sucNum是胜利次数
									}
									",
    primary key(pid, server_id),
    index max_honor(max_honor, honor_time, uid)
)default charset utf8 engine = InnoDb;