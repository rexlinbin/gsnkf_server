set names utf8;

create table if not exists t_guild_record
(
	grid int unsigned not null comment '记录id',
	uid int unsigned not null comment '成员id',
	guild_id int unsigned not null comment '公会id',
	record_type int unsigned not null comment '记录类型',
	record_data int unsigned not null comment '记录数量',
	record_time int unsigned not null comment '记录时间',
	va_info blob not null comment '动态信息',
	primary key(grid),
	key guild_id_time(guild_id, record_time),
	key uid_time(uid, record_time)
)engine = InnoDb default charset utf8;