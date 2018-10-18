set names utf8;

create table if not exists t_arena_msg
(
	id 				int unsigned auto_increment not null comment '消息id',
	attack_uid 		int unsigned not null comment '进攻方用户id',
	attack_name 	char(20) not null comment '进攻方用户名字',
	defend_uid 		int unsigned not null comment '防守方用户id',
	defend_name 	char(20) not null comment '防守方用户名字',
	attack_time 	int unsigned not null comment '进攻时间',
	attack_position int unsigned not null comment '进攻方排名',
	defend_position int unsigned not null comment '防守方排名',
	attack_res 		int unsigned not null comment '挑战结果',
	attack_replay 	int unsigned not null comment '战报id',
	primary key(id),
	index attack_uid_attack_time(attack_uid, attack_time),
	index defend_uid_attack_time(defend_uid, attack_time),
	index attack_time(attack_time)
)engine = InnoDb default charset utf8;

