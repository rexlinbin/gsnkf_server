--   在跨服机器上使用
set names utf8;
CREATE TABLE IF NOT EXISTS t_guild_war_procedure
(
	session			int unsigned not null comment "第几次跨服战",
    team_id 		int unsigned not null comment "分组Id",
    round 			int unsigned not null comment "第几轮比赛",
   	status			int unsigned not null comment "状态",
   	sub_round		int unsigned not null comment "小轮",
   	sub_status		int unsigned not null comment "小轮状态",
    update_time 	int unsigned not null comment "日期",    
    primary key(team_id, round)
)default charset utf8 engine = InnoDb;