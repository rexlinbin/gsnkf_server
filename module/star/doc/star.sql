set names utf8;

create table if not exists t_star(
	star_id		 int unsigned not null  comment '名将id',
	star_tid     int unsigned not null  comment '名将模板id',
	uid 	 	 int unsigned not null  comment '用户id',
	level		 int unsigned not null  comment '好感度等级',
	total_exp	 int unsigned not null  comment '好感度总值',
	feel_skill   int unsigned not null  comment '感悟技能id',
	feel_level   int unsigned not null  comment '感悟度等级',
	feel_total_exp int unsigned not null  comment '感悟度总值',
	pass_hcopy_num int unsigned not null  comment '武将列传副本通关次数',
    primary key(star_id),
    index uid(uid)
)engine = InnoDb default charset utf8;