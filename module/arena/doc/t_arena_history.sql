set names utf8;

create table if not exists t_arena_history
(
	uid				int(10) unsigned not null comment '用户id',
	position		int(10) unsigned not null comment '排名',
	update_time		int(10) unsigned not null comment '更新时间',
	primary key(position),
	index uid(uid)
)default charset utf8 engine = InnoDb;
