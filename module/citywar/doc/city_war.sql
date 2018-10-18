set names utf8;

create table if not exists t_city_war 
(
	city_id int unsigned not null comment '城池id',
	city_defence int unsigned not null comment '城防',
	defence_time int unsigned not null comment '城防更新时间',
	last_gid int unsigned not null comment '实际占领军团id',
	curr_gid int unsigned not null comment '当前占领军团id',
	occupy_time int unsigned not null comment '军团占领时间',
	signup_end_timer int unsigned not null comment '报名结束的timer',
	battle_end_timer int unsigned not null comment '战斗结束的timer',
	va_city_war blob not null comment '记录每场战斗参战的双方军团成员,list{军团id{uid,uid,uid}},ruin{date{uid,uid,uid}},offline{0{军团id{uid}}1{军团id{uid}}',
	va_reward blob not null comment '记录占领城池军团的成员,list{uid=>type,uid=>type,uid=>type}}',
	primary key(city_id)
)default charset utf8 engine = InnoDb;