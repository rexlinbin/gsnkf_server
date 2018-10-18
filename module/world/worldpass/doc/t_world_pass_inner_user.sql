set names utf8;
create table if not exists t_world_pass_inner_user
(
	pid int unsigned not null comment "玩家pid",
	server_id int unsigned not null comment "所在服server_id",
	uid int unsigned not null comment "玩家uid",
	passed_stage int unsigned not null comment "当前通关的最大关卡0-6",
	max_point int unsigned not null comment "本轮闯关赛截止目前为止获得的最大积分",
	max_point_time int unsigned not null comment "本轮闯关赛截止目前为止获得的最大积分的时间", 
	curr_point int unsigned not null comment "本次闯关的总积分",
	hell_point int unsigned not null comment "炼狱积分，商店会消耗",
	atk_num int unsigned not null comment "攻击次数",
	buy_atk_num int unsigned not null comment "购买的攻击次数",
	refresh_num int unsigned not null comment "玩家刷新武将候选列表的次数",
	update_time int unsigned not null comment "更新时间",
	reward_time int unsigned not null comment "发奖时间",
    va_extra blob not null comment "
									choice => array
									[
                                        index => htid index取值0-4，代表5个备选武将格子
									]
                                    formation => array
									[
                                        index => htid index取值0-5，代表6个位置
									]
                                    point => array
									[
                                        index => point index取值0-n
									]
									",
    primary key(pid, server_id)
)default charset utf8 engine = InnoDb;