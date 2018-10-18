set names utf8;

create table if not exists t_black
(
	uid 					int unsigned not null comment '用户id',
	
	va_black 				blob not null comment 'black：array(uid)',
	
	primary key(uid)
)engine = InnoDb default charset utf8;