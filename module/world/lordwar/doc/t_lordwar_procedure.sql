
set names utf8;

CREATE TABLE IF NOT EXISTS t_lordwar_procedure(

    team_id 		int unsigned not null comment "分组ID",
    team_type 		int unsigned not null default 0 comment "组别, 胜者组为1, 负者组为 2",
    round 			int unsigned not null comment "第几轮比赛",
    sess 			int unsigned not null comment "第几次跨服战",
   	status			int unsigned not null comment "状态",
    update_time 	int unsigned not null comment "日期",
    va_procedure	blob not null comment 'lordArr =>array(),recordArr => array(subRound => array())',
    
    primary key(round, team_id, team_type)
)default charset utf8 engine = InnoDb;
