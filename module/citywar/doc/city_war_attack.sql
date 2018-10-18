set names utf8;

create table if not exists t_city_war_attack 
(
	signup_id int unsigned not null comment '报名id',
	signup_time int unsigned not null comment '报名时间',
	city_id int unsigned not null comment '城池id',
	attack_gid int unsigned not null comment '攻击方军团id',
	defend_gid int unsigned not null comment '防守方军团id,0未知,1NPC,其他军团',
	attack_timer int unsigned not null comment '战斗timer',
	attack_replay int unsigned not null comment '战斗录像id',
	attack_result tinyint unsigned not null comment '战斗结果：0输1赢',
	attack_contri int unsigned not null comment '攻击方周贡献值',
	primary key(signup_id),
	key(signup_time),
	key(city_id),
	key(attack_timer)
)default charset utf8 engine = InnoDb;