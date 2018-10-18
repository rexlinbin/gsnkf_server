set names utf8;

create table if not exists t_city_war_user 
(
	uid int unsigned not null comment '用户id',
	cur_city_id int unsigned not null comment '正在参与哪个城池的战斗',
	enter_time int unsigned not null comment '进入战斗的时间',
	reward_time int unsigned not null comment '领奖时间',
	mend_time int unsigned not null comment '修复城防时间',
	ruin_time int unsigned not null comment '破坏城防时间',
	va_city_war_user blob not null comment '记录用户在各个城池中的鼓舞和连胜信息, info{$cityId{win{buy,add},inspire{time,attack,defend}}},offline{time,round{0=>cityId,1=>cityId}}',
	primary key(uid)
)default charset utf8 engine = InnoDb;