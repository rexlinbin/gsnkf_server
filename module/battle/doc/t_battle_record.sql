create table if not exists t_battle_record_0(
	brid int unsigned not null comment '战斗记录id',
	record_type tinyint unsigned not null default 1 comment '记录类型，1表示临时，2表示永久',
	record_time int unsigned not null comment '记录时间',
	record_data blob not null comment '战斗记录',	
	primary key(brid),
	index record_time(record_time)
)engine = MyISAM default charset utf8;

create table if not exists t_battle_record_1 like t_battle_record_0;
create table if not exists t_battle_record_2 like t_battle_record_0;
create table if not exists t_battle_record_3 like t_battle_record_0;
create table if not exists t_battle_record_4 like t_battle_record_0;
create table if not exists t_battle_record_5 like t_battle_record_0;
create table if not exists t_battle_record_6 like t_battle_record_0;
create table if not exists t_battle_record_7 like t_battle_record_0;
create table if not exists t_battle_record_8 like t_battle_record_0;
create table if not exists t_battle_record_9 like t_battle_record_0;
