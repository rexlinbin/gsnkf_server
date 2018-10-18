set names utf8;
create table if not exists t_retrieve
(
	uid			int(10) unsigned not null comment '用户id',
	va_extra 	blob not null comment 'retrieve_type=>retrieve_time',
	primary key(uid)
)default charset utf8 engine = InnoDb;