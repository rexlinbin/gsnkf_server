set names utf8;
create table if not exists t_defeat_replay(
	uid int unsigned not null comment "用户id",
	level int unsigned not null comment "玩家打败据点时刻的等级",
	base_id int unsigned not null comment "据点ID",
	base_level tinyint unsigned not null comment "据点难度级别",
    va_fight_record blob not null comment "战斗录像",
    primary key(base_id,base_level,uid)
)default charset utf8 engine = InnoDb;


#base_level的取值：0npc 1simple 2normal 3hard