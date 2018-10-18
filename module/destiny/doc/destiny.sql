set names utf8;

CREATE TABLE IF NOT EXISTS t_destiny(
	uid 			int unsigned not null comment '用户id',
	cur_destiny   	int unsigned not null comment '当前的天命id',
	va_destiny 		blob not null comment '',
	primary key(uid)
)engine = InnoDb default charset utf8;
