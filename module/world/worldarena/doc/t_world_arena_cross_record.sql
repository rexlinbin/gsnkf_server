set names utf8;
create table if not exists t_world_arena_cross_record
(
	id 						int unsigned auto_increment not null comment '消息id',
	team_id 				int unsigned not null comment '分组id',
	room_id 				int unsigned not null comment '房间id',
	attacker_server_id 		int unsigned not null comment '攻方服务器id',
	attacker_pid			int unsigned not null comment '攻方pid',
	attacker_uname 			char(20) not null comment '攻方名字',
	attacker_htid	 		int unsigned not null comment '攻方htid',
	attacker_rank 			int unsigned not null comment '攻方名次',
	attacker_conti 			int unsigned not null comment '攻方连胜次数',
	attacker_terminal_conti int unsigned not null comment '攻方终结对方连胜次数',
	defender_server_id 		int unsigned not null comment '守方服务器id',
	defender_pid 			int unsigned not null comment '守方pid',
	defender_uname 			char(20) not null comment '守方名字',
	defender_htid	 		int unsigned not null comment '守方htid',
	defender_rank 			int unsigned not null comment '守方名次',
	defender_conti 			int unsigned not null comment '守方连胜次数',
	defender_terminal_conti int unsigned not null comment '守方终结对方连胜次数',
	attack_time 			int unsigned not null comment '时间',
	result 					int unsigned not null comment '结果',
	brid 					char(20) not null comment '战报id',
	primary key(id),
	index attacker_attack_time(team_id, room_id, attacker_server_id, attacker_pid, attack_time),
	index attacker_conti_time(team_id, room_id, attacker_conti, attack_time),
	index attacker_terminal_conti_time(team_id, room_id, attacker_terminal_conti, attack_time),
	index defender_attack_time(team_id, room_id, defender_server_id, defender_pid, attack_time),
	index attack_time(team_id, room_id, attack_time)
)engine = InnoDb default charset utf8;

