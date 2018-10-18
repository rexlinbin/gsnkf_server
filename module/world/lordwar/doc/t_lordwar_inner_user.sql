set names utf8;

CREATE TABLE IF NOT EXISTS t_lordwar_inner_user(
	pid 				int unsigned not null comment '用户ID',
    server_id 			int unsigned not null comment '服务器id',
    uid 				int unsigned not null comment '用户ID',
    team_type 			int unsigned not null comment '组别, 初始为0, 胜者组为1, 负者组为 2',
    winner_losenum 		int unsigned not null comment '胜者组海选时候失败的次数',
    loser_losenum 		int unsigned not null comment '败者组海选时候失败的次数',
    support_pid 		int unsigned not null comment '助威对象',
    support_serverid 	int unsigned not null comment '助威对象的所在服务器ID',
    support_round 		int unsigned not null comment '助威轮次',
    worship_time 		int unsigned not null comment '膜拜时刻',
    update_fmt_time 	int unsigned not null comment '更新战斗力时刻',
    register_time 		int unsigned not null comment '报名时间',
    last_join_time		int unsigned not null comment '最近一次的参与时间',
    bless_receive_time 	int unsigned not null comment '全服奖励的时间',
    va_lord 			blob not null comment '助威对象(需要记录助威的轮数前段显示用)/战斗信息(战斗方法的参数)
												supportList => array(array(serverId,pid...))
												fightPara =>array()
',
    va_lord_extra 		blob not null comment '战报等非玩家自己修改的信息0 => array( round =>int, teamType => int, subArr = array( subRound => array(attacker => array() ,defender => array(),replyId=>str,))',
    primary key(pid,server_id),
    key(last_join_time),
    key(support_round)
)default charset utf8 engine = InnoDb;
