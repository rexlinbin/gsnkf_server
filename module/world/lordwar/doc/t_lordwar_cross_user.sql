set names utf8;

CREATE TABLE IF NOT EXISTS t_lordwar_cross_user( 

	pid 				int unsigned not null comment "用户ID",
	server_id 			int unsigned not null comment "用户的所在服务器ID",
	team_id 			int unsigned not null comment "分组ID",
	team_type 			int unsigned not null default 0 comment "组别, 初始为0, 胜者组为1, 负者组为 2",
	register_time 		int unsigned not null comment "报名时间",
    winner_losenum 		int unsigned not null comment "群雄组海选时候失败的次数",
    loser_losenum 		int unsigned not null comment "初出茅庐组海选时候失败的次数",
    
    primary key(pid,server_id)
)default charset utf8 engine = InnoDb;
